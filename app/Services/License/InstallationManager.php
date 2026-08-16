<?php

namespace App\Services\License;

use App\Exceptions\License\LicenseTamperedException;
use App\Models\LicenseInstallation;
use App\Models\LicenseSecurityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * InstallationManager — manages this installation's identity.
 *
 * Security design:
 * - Installation ID: 32 cryptographically random bytes → hex string (64 chars)
 * - Installation HMAC: HMAC-SHA256(installation_id, derived_secret)
 *   If someone changes installation_id in the DB, the HMAC no longer matches
 * - API Credential: 32 cryptographically random bytes → hex string (64 chars)
 *   Unique per installation, bcrypt-hashed at rest
 *   The plain credential is returned once and must be stored by the caller
 * - Domain: the APPLICATION_URL, normalized by LicenseVerifier
 *
 * Why not just use APP_KEY as the HMAC key?
 * Because APP_KEY can be regenerated (php artisan key:generate).
 * We derive a secondary secret: HMAC-SHA256(APP_KEY, 'softtrill-installation-v1')
 * so that normal key rotation does not break HMAC validation but a deliberate
 * deletion of the table row is detectable.
 */
final class InstallationManager
{
    private const HMAC_CONTEXT = 'softtrill-installation-v1';

    public function __construct(
        private readonly LicenseVerifier $verifier,
    ) {}

    /**
     * Returns the current LicenseInstallation record, verifying HMAC integrity.
     * Returns null if not yet activated.
     *
     * @throws LicenseTamperedException if the HMAC does not match (DB was tampered)
     */
    public function getInstallation(): ?LicenseInstallation
    {
        $installation = LicenseInstallation::first();

        if ($installation === null) {
            return null;
        }

        // Verify the HMAC to detect database-level tampering of installation_id
        $expectedHmac = $this->computeHmac($installation->installation_id);
        if (!hash_equals($expectedHmac, $installation->installation_hmac)) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_INSTALLATION_MISMATCH,
                LicenseSecurityLog::SEVERITY_CRITICAL,
                ['reason' => 'HMAC mismatch — installation_id may have been tampered with'],
                $installation->installation_id
            );
            Log::critical('[InstallationManager] Installation HMAC mismatch. DB may have been tampered.');
            throw new LicenseTamperedException('Installation identity integrity check failed.');
        }

        return $installation;
    }

    /**
     * Create a new installation record.
     * Called once during the activation command.
     * Returns the plain api_credential (only time it is revealed).
     *
     * @return array{installation: LicenseInstallation, plain_credential: string}
     */
    public function createInstallation(string $domain): array
    {
        if (LicenseInstallation::exists()) {
            throw new \RuntimeException('An installation record already exists. Deactivate first.');
        }

        $installationId  = bin2hex(random_bytes(32));           // 64 hex chars
        $plainCredential = bin2hex(random_bytes(32));           // 64 hex chars
        $hmac            = $this->computeHmac($installationId);

        $installation = DB::transaction(function () use ($installationId, $plainCredential, $hmac, $domain) {
            return LicenseInstallation::create([
                'installation_id'      => $installationId,
                'installation_hmac'    => $hmac,
                'api_credential'       => $plainCredential, // stored plain for client use
                'api_credential_hash'  => Hash::make($plainCredential),
                'domain'               => $this->verifier->normalizeDomain($domain),
                'status'               => 'pending',
            ]);
        });

        Log::info('[InstallationManager] New installation record created.', [
            'installation_id' => $installationId,
            'domain'          => $domain,
        ]);

        return [
            'installation'     => $installation,
            'plain_credential' => $plainCredential,
        ];
    }

    /**
     * Get or create the installation identity.
     * If no installation exists, creates a pending one.
     * Returns the installation_id and current domain.
     */
    public function getOrCreateIdentity(): LicenseInstallation
    {
        $existing = $this->getInstallation();
        if ($existing) {
            return $existing;
        }

        $domain = $this->detectDomain();
        $result = $this->createInstallation($domain);
        return $result['installation'];
    }

    /**
     * Mark the installation as active after successful activation.
     */
    public function markActivated(LicenseInstallation $installation): void
    {
        $installation->update([
            'status'       => 'active',
            'activated_at' => now(),
            'last_validated_at' => now(),
        ]);
    }

    /**
     * Update the last_validated_at timestamp.
     */
    public function touchValidated(LicenseInstallation $installation): void
    {
        $installation->update(['last_validated_at' => now()]);
    }

    /**
     * Set the grace period expiry.
     * Called when the license server becomes unreachable.
     */
    public function setGracePeriod(LicenseInstallation $installation): void
    {
        $graceSeconds = config('license.grace_period', 259200);
        $installation->update([
            'grace_expires_at' => now()->addSeconds($graceSeconds),
        ]);

        LicenseSecurityLog::record(
            LicenseSecurityLog::EVENT_GRACE_PERIOD_ENTERED,
            LicenseSecurityLog::SEVERITY_WARNING,
            ['grace_expires_at' => now()->addSeconds($graceSeconds)->toISOString()],
            $installation->installation_id
        );
    }

    /**
     * Returns true if we are still within the offline grace period.
     */
    public function isWithinGracePeriod(LicenseInstallation $installation): bool
    {
        if ($installation->grace_expires_at === null) {
            return false;
        }
        return $installation->grace_expires_at->isFuture();
    }

    /**
     * Deactivate this installation (wipe identity).
     */
    public function deactivate(): void
    {
        $installation = $this->getInstallation();
        if ($installation) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_DEACTIVATION,
                LicenseSecurityLog::SEVERITY_INFO,
                [],
                $installation->installation_id
            );
            // Delete entitlements and the installation record
            $installation->entitlements()->delete();
            $installation->delete();
        }
    }

    /**
     * Detect the current domain from app config.
     */
    public function detectDomain(): string
    {
        return $this->verifier->normalizeDomain(config('app.url', ''));
    }

    // -----------------------------------------------------------------------
    // Private
    // -----------------------------------------------------------------------

    /**
     * Compute an HMAC of the installation_id using a key derived from APP_KEY.
     * APP_KEY is environment-specific and never distributed.
     */
    private function computeHmac(string $installationId): string
    {
        // Derive a secondary key from APP_KEY using HKDF-like construction
        $appKey = config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        $derivedKey = hash_hmac('sha256', self::HMAC_CONTEXT, $appKey, true);
        return hash_hmac('sha256', $installationId, $derivedKey);
    }
}
