<?php

namespace App\Services\License;

use App\Exceptions\License\LicenseActivationException;
use App\Exceptions\License\LicenseExpiredException;
use App\Exceptions\License\LicenseRevokedException;
use App\Exceptions\License\LicenseServerUnavailableException;
use App\Exceptions\License\LicenseTamperedException;
use App\Models\LicenseEntitlement;
use App\Models\LicenseInstallation;
use App\Models\LicenseSecurityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LicenseManager — orchestrates the full license lifecycle.
 *
 * This is the primary entry point for the rest of the application.
 * It coordinates LicenseClient, LicenseVerifier, and InstallationManager.
 *
 * IMPORTANT: This class does NOT expose any raw payload data directly.
 * Use EntitlementManager to access verified entitlement values.
 *
 * Validation strategy:
 * 1. Load signed payload from DB (license_entitlements)
 * 2. Verify Ed25519 signature — fail-closed if invalid
 * 3. Check cached_until — if expired, call license server for fresh payload
 * 4. If server unreachable, check grace period — fail-closed after grace
 * 5. Cache the verified status in Laravel cache to avoid DB hit on every request
 *
 * The cache stores ONLY a boolean + expiry, NOT entitlement values.
 * Entitlement values are ALWAYS read from the verified signed payload in DB.
 */
final class LicenseManager
{
    private const CACHE_KEY      = 'softtrill_license_ok';
    private const CACHE_DURATION = 300; // 5 minutes — short; verified payload in DB is authoritative

    private ?array $resolvedPayload = null;

    public function __construct(
        private readonly LicenseVerifier    $verifier,
        private readonly LicenseClient      $client,
        private readonly InstallationManager $installationManager,
    ) {}

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Boot the license system. Called from AppServiceProvider on every request.
     * Validates the license and throws an appropriate exception if invalid.
     * Skipped in CLI context.
     *
     * @throws LicenseExpiredException
     * @throws LicenseRevokedException
     * @throws LicenseTamperedException
     * @throws LicenseServerUnavailableException
     */
    public function boot(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        // Fast path: check the short-lived in-memory/cache flag
        if (Cache::has(self::CACHE_KEY)) {
            return; // recently validated — skip DB hit
        }

        $this->validateOrFail();
    }

    /**
     * Validate the license, returning the verified payload on success.
     * Forces a fresh check (ignores the short-lived cache).
     *
     * @throws LicenseExpiredException
     * @throws LicenseRevokedException
     * @throws LicenseTamperedException
     * @throws LicenseServerUnavailableException
     */
    public function validateOrFail(): array
    {
        $installation = $this->installationManager->getInstallation();

        if ($installation === null || $installation->status === 'pending') {
            Log::warning('[LicenseManager] No active installation found.');
            throw new LicenseTamperedException('This installation has not been activated. Run: php artisan softtrill:license:activate');
        }

        if ($installation->status === 'deactivated') {
            throw new LicenseRevokedException('This installation has been deactivated.');
        }

        // Load the stored signed payload
        $entitlement = LicenseEntitlement::where('installation_id', $installation->installation_id)
            ->latest()
            ->first();

        if ($entitlement === null) {
            // No payload stored — must re-validate with server
            return $this->revalidateWithServer($installation);
        }

        // Verify the signature — ALWAYS — before trusting any data
        try {
            $payload = $this->verifier->verifySignedPayload(
                signedPayloadBase64: $entitlement->signed_payload,
                expectedInstallationId: $installation->installation_id,
                expectedDomain: $installation->domain
            );
        } catch (LicenseTamperedException $e) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_SIGNATURE_FAILURE,
                LicenseSecurityLog::SEVERITY_CRITICAL,
                ['error' => $e->getMessage()],
                $installation->installation_id
            );
            throw $e;
        }

        // Check payload-level status
        $this->assertPayloadStatus($payload, $installation->installation_id);

        // Check if license has expired in the payload
        $expiresAt = Carbon::parse($payload['expires_at']);
        if ($expiresAt->isPast()) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_LICENSE_EXPIRED,
                LicenseSecurityLog::SEVERITY_WARNING,
                ['expires_at' => $payload['expires_at']],
                $installation->installation_id
            );
            throw new LicenseExpiredException("License expired at: {$payload['expires_at']}");
        }

        // Check if validation interval has expired (time to re-fetch from server)
        if ($entitlement->needsRevalidation()) {
            try {
                $payload = $this->revalidateWithServer($installation);
            } catch (LicenseServerUnavailableException $e) {
                // Server unreachable — use grace period
                $payload = $this->handleServerUnreachable($installation, $entitlement, $payload);
            }
        } else {
            // Reset grace period since we have a valid entitlement
            if ($installation->grace_expires_at !== null) {
                $installation->update(['grace_expires_at' => null]);
            }
        }

        // Set the short-lived validation cache flag
        Cache::put(self::CACHE_KEY, true, self::CACHE_DURATION);
        $this->resolvedPayload = $payload;
        return $payload;
    }

    /**
     * Returns the verified payload for the current request.
     * Must be called after validateOrFail().
     *
     * @throws LicenseTamperedException if not yet validated
     */
    public function getVerifiedPayload(): array
    {
        if ($this->resolvedPayload !== null) {
            return $this->resolvedPayload;
        }

        // Load from DB and verify (happens on the first call after cache hit)
        $installation = $this->installationManager->getInstallation();
        if ($installation === null) {
            throw new LicenseTamperedException('No installation found.');
        }

        $entitlement = LicenseEntitlement::where('installation_id', $installation->installation_id)
            ->latest()
            ->first();

        if ($entitlement === null) {
            throw new LicenseTamperedException('No license entitlement found.');
        }

        $payload = $this->verifier->verifySignedPayload(
            signedPayloadBase64: $entitlement->signed_payload,
            expectedInstallationId: $installation->installation_id,
            expectedDomain: $installation->domain
        );

        $this->resolvedPayload = $payload;
        return $payload;
    }

    /**
     * Store a signed payload received from the license server.
     * Verifies the signature before storing.
     */
    public function storeSignedPayload(
        LicenseInstallation $installation,
        string $signedPayloadBase64
    ): LicenseEntitlement {
        // Verify before storing
        $payload = $this->verifier->verifySignedPayload(
            signedPayloadBase64: $signedPayloadBase64,
            expectedInstallationId: $installation->installation_id,
            expectedDomain: $installation->domain
        );

        $payloadHash = $this->verifier->payloadHash($signedPayloadBase64);
        $cachedUntil = now()->addSeconds(config('license.validation_interval', 86400));

        return DB::transaction(function () use (
            $installation, $signedPayloadBase64, $payload, $payloadHash, $cachedUntil
        ) {
            // Replace existing entitlement for this installation
            LicenseEntitlement::where('installation_id', $installation->installation_id)->delete();

            return LicenseEntitlement::create([
                'installation_id'   => $installation->installation_id,
                'signed_payload'    => $signedPayloadBase64,
                'payload_hash'      => $payloadHash,
                'license_id'        => $payload['license_id'] ?? null,
                'payload_issued_at' => isset($payload['issued_at'])  ? Carbon::parse($payload['issued_at'])  : now(),
                'payload_expires_at'=> isset($payload['expires_at']) ? Carbon::parse($payload['expires_at']) : null,
                'cached_until'      => $cachedUntil,
                'cached_status'     => $payload['status'] ?? 'unknown',
            ]);
        });
    }

    /**
     * Clear the short-lived validation cache.
     * Forces re-validation on the next request.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->resolvedPayload = null;
    }

    /**
     * Sends an asynchronous heartbeat to the license server with the latest telemetry.
     * Safe to call on login/logout without blocking the response.
     */
    public function sendHeartbeat(): void
    {
        $installation = $this->installationManager->getInstallation();
        if ($installation === null || $installation->status !== 'active') return;

        dispatch(function () use ($installation) {
            try {
                $this->client->heartbeat(
                    installationId: $installation->installation_id,
                    apiCredential: $installation->api_credential,
                    telemetry: $this->gatherTelemetry()
                );
            } catch (\Throwable $e) {
                Log::warning('[LicenseManager] Failed to send async heartbeat: ' . $e->getMessage());
            }
        })->afterResponse();
    }

    // -----------------------------------------------------------------------
    // Private: server re-validation
    // -----------------------------------------------------------------------

    private function revalidateWithServer(LicenseInstallation $installation): array
    {
        Log::info('[LicenseManager] Revalidating with license server.', [
            'installation_id' => $installation->installation_id,
        ]);

        try {
            $response = $this->client->validate(
                installationId: $installation->installation_id,
                apiCredential: $installation->api_credential,
                domain: $installation->domain,
                telemetry: $this->gatherTelemetry()
            );

            $signedPayload = $response['signed_payload'] ?? null;
            if (empty($signedPayload)) {
                throw new \RuntimeException('License server returned no signed_payload.');
            }

            $entitlement = $this->storeSignedPayload($installation, $signedPayload);
            $this->installationManager->touchValidated($installation);

            // Clear grace period since we successfully validated
            $installation->update(['grace_expires_at' => null]);

            $payload = $this->verifier->verifySignedPayload(
                signedPayloadBase64: $signedPayload,
                expectedInstallationId: $installation->installation_id,
                expectedDomain: $installation->domain
            );

            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_VALIDATION_SUCCESS,
                LicenseSecurityLog::SEVERITY_INFO,
                ['license_id' => $payload['license_id'] ?? 'unknown'],
                $installation->installation_id
            );

            return $payload;

        } catch (LicenseTamperedException $e) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_SIGNATURE_FAILURE,
                LicenseSecurityLog::SEVERITY_CRITICAL,
                ['error' => $e->getMessage()],
                $installation->installation_id
            );
            throw $e;
        } catch (\RuntimeException $e) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_SERVER_UNREACHABLE,
                LicenseSecurityLog::SEVERITY_WARNING,
                ['error' => $e->getMessage()],
                $installation->installation_id
            );
            throw new LicenseServerUnavailableException($e->getMessage());
        }
    }

    /**
     * Handle the case where the license server is unreachable.
     * Uses the existing valid signed payload within the grace period.
     * Fails closed after grace expires.
     */
    private function handleServerUnreachable(
        LicenseInstallation $installation,
        LicenseEntitlement $entitlement,
        array $existingPayload
    ): array {
        // Set grace period if not already set
        if ($installation->grace_expires_at === null) {
            $this->installationManager->setGracePeriod($installation);
        }

        // Refresh from DB
        $installation->refresh();

        if (!$this->installationManager->isWithinGracePeriod($installation)) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_GRACE_PERIOD_EXPIRED,
                LicenseSecurityLog::SEVERITY_CRITICAL,
                ['grace_expired_at' => $installation->grace_expires_at?->toISOString()],
                $installation->installation_id
            );
            Log::critical('[LicenseManager] Grace period expired. Failing closed.');
            throw new LicenseServerUnavailableException(
                'License server unreachable and grace period has expired. Application is locked.'
            );
        }

        Log::warning('[LicenseManager] Using grace period. License server unreachable.', [
            'grace_expires_at' => $installation->grace_expires_at?->toISOString(),
        ]);

        // Bump cached_until briefly so we don't hammer the server on every request
        $entitlement->update(['cached_until' => now()->addMinutes(5)]);

        return $existingPayload;
    }

    private function assertPayloadStatus(array $payload, string $installationId): void
    {
        $status = $payload['status'] ?? 'unknown';

        match (true) {
            $status === 'revoked' || $status === 'suspended' => (function () use ($status, $installationId) {
                LicenseSecurityLog::record(
                    LicenseSecurityLog::EVENT_LICENSE_REVOKED,
                    LicenseSecurityLog::SEVERITY_CRITICAL,
                    ['status' => $status],
                    $installationId
                );
                throw new LicenseRevokedException("License status is: {$status}");
            })(),
            $status !== 'active' => (function () use ($status, $installationId) {
                LicenseSecurityLog::record(
                    LicenseSecurityLog::EVENT_VALIDATION_FAILURE,
                    LicenseSecurityLog::SEVERITY_WARNING,
                    ['status' => $status],
                    $installationId
                );
                throw new LicenseTamperedException("Unexpected license status: {$status}");
            })(),
            default => null,
        };
    }

    /**
     * Gather telemetry data to send to the license server.
     */
    public function gatherTelemetry(): array
    {
        try {
            $totalUsers = \App\Models\User::count();
            $activeQuotaUsers = \App\Models\User::withoutRole('Admin')->count();
            
            $onlineUsers = \App\Models\User::withoutRole('Admin')
                ->where('last_activity_at', '>=', now()->subMinutes(2))
                ->get(['id', 'name', 'email']);
            
            $allUsers = \App\Models\User::withoutRole('Admin')->get(['id', 'name', 'email']);
            
            return [
                'total_users' => $totalUsers,
                'active_quota_users' => $activeQuotaUsers,
                'currently_online_count' => $onlineUsers->count(),
                'online_users_list' => $onlineUsers->toArray(),
                'all_users_list' => $allUsers->toArray(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'last_sync_at' => now()->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            return ['error' => 'Failed to gather telemetry: ' . $e->getMessage()];
        }
    }
}
