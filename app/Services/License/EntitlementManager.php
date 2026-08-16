<?php

namespace App\Services\License;

use App\Exceptions\License\FeatureNotLicensedException;
use App\Exceptions\License\LicenseTamperedException;
use App\Exceptions\License\UserLimitExceededException;
use App\Models\LicenseSecurityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EntitlementManager — provides the application with cryptographically verified entitlement values.
 *
 * ALL values returned by this class are sourced from the Ed25519-verified license payload.
 * No value is read from the database, .env, or local config without passing through
 * LicenseManager::getVerifiedPayload() first.
 *
 * Security note on the boundary:
 * This class is one security layer among several. If a customer replaces `canAddUser()`
 * with `return true;`, they bypass the LOCAL check. However, the actual user creation
 * in UserController must also pass through a DB transaction with a COUNT lock, which
 * derives its limit from THIS class (from the signed payload). Additionally, server-side
 * operations require fresh signed authorization that cannot be forged without the private key.
 *
 * Design intent: Make it hard enough that the cost of circumvention exceeds the license value.
 */
final class EntitlementManager
{
    private ?array $cachedPayload = null;

    public function __construct(
        private readonly LicenseManager $licenseManager,
    ) {}

    // -----------------------------------------------------------------------
    // Core entitlement checks
    // -----------------------------------------------------------------------

    /**
     * Returns true if the license is active and not expired.
     * Does NOT contact the server — uses the verified cached payload.
     */
    public function isValid(): bool
    {
        try {
            $payload = $this->payload();
            return ($payload['status'] ?? '') === 'active'
                && Carbon::parse($payload['expires_at'])->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Returns true if the given feature is enabled in the signed license payload.
     *
     * @throws FeatureNotLicensedException if feature is not licensed
     */
    public function assertCanUse(string $feature): void
    {
        $payload  = $this->payload();
        $features = $payload['features'] ?? [];

        if (!isset($features[$feature]) || !$features[$feature]) {
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_FEATURE_DENIED,
                LicenseSecurityLog::SEVERITY_WARNING,
                ['feature' => $feature],
                $payload['installation_id'] ?? null
            );
            throw new FeatureNotLicensedException($feature);
        }
    }

    /**
     * Returns true if the feature is enabled (non-throwing version).
     */
    public function canUse(string $feature): bool
    {
        try {
            $payload  = $this->payload();
            $features = $payload['features'] ?? [];
            return isset($features[$feature]) && (bool) $features[$feature];
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Returns the maximum number of users authorized by the signed license.
     * If max_users is 0, it means unlimited.
     */
    public function maxUsers(): int
    {
        $payload = $this->payload();
        return (int) ($payload['max_users'] ?? 0);
    }

    /**
     * Returns the license expiry date from the signed payload.
     */
    public function expiresAt(): Carbon
    {
        $payload = $this->payload();
        return Carbon::parse($payload['expires_at']);
    }

    /**
     * Returns the installation_id from the verified payload.
     */
    public function installationId(): string
    {
        $payload = $this->payload();
        return (string) ($payload['installation_id'] ?? '');
    }

    /**
     * Returns the license_id from the verified payload.
     */
    public function licenseId(): string
    {
        $payload = $this->payload();
        return (string) ($payload['license_id'] ?? '');
    }

    /**
     * Returns the full features array from the signed payload.
     */
    public function features(): array
    {
        $payload = $this->payload();
        return (array) ($payload['features'] ?? []);
    }

    /**
     * Returns an array of all verified entitlement values safe to display in UI.
     */
    public function summary(): array
    {
        try {
            $payload = $this->payload();
            return [
                'license_id'      => $payload['license_id'] ?? null,
                'status'          => $payload['status'] ?? 'unknown',
                'domain'          => $payload['domain'] ?? null,
                'expires_at'      => $payload['expires_at'] ?? null,
                'max_users'       => $payload['max_users'] ?? 0,
                'features'        => $payload['features'] ?? [],
                'product'         => $payload['product'] ?? null,
                'issued_at'       => $payload['issued_at'] ?? null,
            ];
        } catch (\Throwable) {
            return [
                'status'   => 'invalid',
                'max_users' => 0,
                'features'  => [],
            ];
        }
    }

    // -----------------------------------------------------------------------
    // User limit enforcement
    // -----------------------------------------------------------------------

    /**
     * Count currently active (non-admin) users.
     * This query runs INSIDE a DB transaction with a lock for race safety.
     */
    public function currentUserCount(): int
    {
        return User::withoutRole('Admin')->count();
    }

    /**
     * Thread-safe check: can we add one more user?
     *
     * MUST be called inside a DB::transaction() with SELECT FOR UPDATE to prevent
     * race conditions (two requests simultaneously checking count=99 when max=100).
     *
     * @throws UserLimitExceededException if limit is reached
     * @throws LicenseTamperedException   if payload cannot be verified
     */
    public function assertCanAddUser(): void
    {
        $max = $this->maxUsers();

        // 0 means unlimited
        if ($max === 0) {
            return;
        }

        // Lock the installation record to serialize user creation and prevent race conditions
        \App\Models\LicenseInstallation::lockForUpdate()->first();

        // This count must happen inside the same DB transaction as user creation
        $current = $this->currentUserCount();

        if ($current >= $max) {
            $installationId = $this->installationId();
            LicenseSecurityLog::record(
                LicenseSecurityLog::EVENT_USER_LIMIT_EXCEEDED,
                LicenseSecurityLog::SEVERITY_WARNING,
                [
                    'max_users'     => $max,
                    'current_users' => $current,
                ],
                $installationId
            );
            Log::warning('[EntitlementManager] User limit exceeded.', [
                'max_users'     => $max,
                'current_users' => $current,
            ]);
            throw new UserLimitExceededException(
                "User limit of {$max} reached. Current: {$current}."
            );
        }
    }

    /**
     * Non-throwing version for UI display.
     */
    public function canAddUser(): bool
    {
        try {
            $this->assertCanAddUser();
            return true;
        } catch (UserLimitExceededException) {
            return false;
        } catch (\Throwable) {
            return false; // fail closed
        }
    }

    /**
     * Thread-safe check: can this user log in right now?
     * @throws UserLimitExceededException
     */
    public function assertCanLogin(\App\Models\User $user): void
    {
        if ($user->hasRole('Admin')) {
            return; // Admins are immune to quota
        }

        $maxOnline = (int) ($this->payload()['max_online_users'] ?? 0);
        if ($maxOnline === 0) {
            return; // 0 means unlimited
        }

        // Count how many users have been active in the last 2 minutes
        // Exclude the user currently trying to log in (in case they are already counted)
        $currentOnline = \App\Models\User::withoutRole('Admin')
            ->where('id', '!=', $user->id)
            ->where('last_activity_at', '>=', now()->subMinutes(2))
            ->count();

        if ($currentOnline >= $maxOnline) {
            Log::warning('[EntitlementManager] Max simultaneous logins reached.', [
                'max' => $maxOnline,
                'current' => $currentOnline,
                'user_id' => $user->id,
            ]);
            throw new UserLimitExceededException(
                "Maximum simultaneous active users ({$maxOnline}) reached. Please try again later."
            );
        }
    }

    // -----------------------------------------------------------------------
    // Private
    // -----------------------------------------------------------------------

    /**
     * Load and cache the verified payload for this request.
     * Caches in-memory only (per request). Never persisted to a plain cache.
     *
     * @throws \App\Exceptions\License\LicenseException on any verification failure
     */
    private function payload(): array
    {
        if ($this->cachedPayload === null) {
            $this->cachedPayload = $this->licenseManager->getVerifiedPayload();
        }
        return $this->cachedPayload;
    }
}
