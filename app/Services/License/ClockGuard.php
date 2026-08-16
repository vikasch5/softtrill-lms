<?php

namespace App\Services\License;

use App\Exceptions\License\LicenseTamperedException;
use App\Models\LicenseInstallation;
use App\Models\LicenseSecurityLog;
use Illuminate\Support\Facades\Log;

/**
 * ClockGuard — protects against system clock rollback.
 * 
 * Compares the current local time with the last trusted server time.
 * If local time < last trusted server time (minus tolerance), it detects rollback.
 */
final class ClockGuard
{
    private const CLOCK_TOLERANCE_SECONDS = 300; // 5 minutes tolerance

    public function __construct(
        private readonly InstallationManager $installationManager
    ) {}

    /**
     * Check if the system clock has been rolled back.
     * @throws LicenseTamperedException if rollback is detected.
     */
    public function checkClock(LicenseInstallation $installation, array $payload): void
    {
        $serverTime = $payload['server_time'] ?? null;
        if (!$serverTime) {
            return; // Legacy payload without server time
        }

        $now = time();

        // Check against previously stored server time
        if ($installation->last_server_time) {
            if ($now < ($installation->last_server_time - self::CLOCK_TOLERANCE_SECONDS)) {
                $this->handleRollback($installation, $now, $installation->last_server_time);
            }
        }

        // Check if the current payload's server time is older than the stored server time
        if ($installation->last_server_time && $serverTime < ($installation->last_server_time - self::CLOCK_TOLERANCE_SECONDS)) {
             $this->handleRollback($installation, $serverTime, $installation->last_server_time);
        }

        // Update trusted server time if it's newer
        if (!$installation->last_server_time || $serverTime > $installation->last_server_time) {
            $installation->update([
                'last_server_time' => $serverTime,
                'last_successful_validation_at' => now(),
            ]);
        }
    }

    private function handleRollback(LicenseInstallation $installation, int $currentTime, int $lastTrustedTime): void
    {
        LicenseSecurityLog::record(
            LicenseSecurityLog::EVENT_VALIDATION_FAILURE,
            LicenseSecurityLog::SEVERITY_CRITICAL,
            [
                'reason' => 'Clock rollback detected.',
                'current_time' => date('c', $currentTime),
                'last_trusted_time' => date('c', $lastTrustedTime),
            ],
            $installation->installation_id
        );

        Log::critical('[ClockGuard] Clock rollback detected.', [
            'current_time' => date('c', $currentTime),
            'last_trusted_time' => date('c', $lastTrustedTime),
        ]);

        // Place into RESTRICTED/GRACE state by throwing exception
        throw new LicenseTamperedException('System clock anomaly detected. Time appears to have been rolled back.');
    }
}
