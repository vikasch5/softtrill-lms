<?php

namespace App\Console\Commands\License;

use App\Services\License\EntitlementManager;
use App\Services\License\InstallationManager;
use App\Services\License\LicenseManager;
use Illuminate\Console\Command;

/**
 * Display the current license status and entitlement summary.
 */
class LicenseStatus extends Command
{
    protected $signature = 'softtrill:license:status';
    protected $description = 'Display the current license status and entitlement details.';

    public function __construct(
        private readonly LicenseManager      $licenseManager,
        private readonly EntitlementManager  $entitlementManager,
        private readonly InstallationManager $installationManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════════════════════╗');
        $this->info('  ║   Softtrill LMS — License Status                  ║');
        $this->info('  ╚══════════════════════════════════════════════════╝');
        $this->info('');

        // Installation info
        try {
            $installation = $this->installationManager->getInstallation();
        } catch (\Throwable $e) {
            $this->error('Installation identity check failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($installation === null) {
            $this->warn('  Status: NOT ACTIVATED');
            $this->line('  Run: php artisan softtrill:license:activate');
            return self::SUCCESS;
        }

        $this->table(['Field', 'Value'], [
            ['Installation ID',  $installation->installation_id],
            ['Status',           strtoupper($installation->status)],
            ['Domain',           $installation->domain ?? '(not set)'],
            ['Activated At',     $installation->activated_at?->toDateTimeString() ?? '—'],
            ['Last Validated',   $installation->last_validated_at?->toDateTimeString() ?? '—'],
            ['Grace Expires At', $installation->grace_expires_at?->toDateTimeString() ?? '—'],
        ]);

        // Entitlement info
        try {
            $payload = $this->licenseManager->validateOrFail();
            $summary = $this->entitlementManager->summary();

            $this->info('');
            $this->info('  License Entitlement (from signed payload):');
            $this->table(['Field', 'Value'], [
                ['License ID',   $summary['license_id'] ?? '—'],
                ['Status',       strtoupper($summary['status'])],
                ['Product',      $summary['product'] ?? '—'],
                ['Expires At',   $summary['expires_at'] ?? '—'],
                ['Max Users',    $summary['max_users'] === 0 ? 'Unlimited' : $summary['max_users']],
                ['Current Users',$this->entitlementManager->currentUserCount()],
                ['Features',     empty($summary['features']) ? 'none' : implode(', ', array_keys(array_filter($summary['features'])))],
                ['Issued At',    $summary['issued_at'] ?? '—'],
            ]);

            $this->info('');
            $this->info('  ✓ License is valid and cryptographically verified.');

        } catch (\Throwable $e) {
            $this->info('');
            $this->error('  License entitlement check failed:');
            $this->line('  ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
