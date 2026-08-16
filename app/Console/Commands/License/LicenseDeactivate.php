<?php

namespace App\Console\Commands\License;

use App\Services\License\InstallationManager;
use App\Services\License\LicenseClient;
use App\Services\License\LicenseManager;
use Illuminate\Console\Command;

/**
 * Deactivate this LMS installation.
 * Notifies the license server and clears local identity.
 */
class LicenseDeactivate extends Command
{
    protected $signature = 'softtrill:license:deactivate
                            {--reason= : Reason for deactivation}
                            {--force : Skip confirmation}';

    protected $description = 'Deactivate this Softtrill LMS installation. This will lock the application.';

    public function __construct(
        private readonly LicenseManager      $licenseManager,
        private readonly LicenseClient       $licenseClient,
        private readonly InstallationManager $installationManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->warn('');
        $this->warn('  WARNING: This will deactivate the license for this installation.');
        $this->warn('  The application will no longer function until re-activated.');
        $this->warn('');

        if (!$this->option('force') && !$this->confirm('Are you sure you want to deactivate this installation?')) {
            $this->info('Deactivation cancelled.');
            return self::SUCCESS;
        }

        try {
            $installation = $this->installationManager->getInstallation();

            if ($installation === null) {
                $this->warn('No active installation found. Nothing to deactivate.');
                return self::SUCCESS;
            }

            // Notify the license server
            $reason = $this->option('reason') ?? 'manual-deactivation';
            try {
                $this->licenseClient->deactivate(
                    installationId: $installation->installation_id,
                    apiCredential: $installation->api_credential,
                    reason: $reason
                );
                $this->info('License server notified of deactivation.');
            } catch (\Throwable $e) {
                $this->warn('Could not reach license server to notify deactivation: ' . $e->getMessage());
                $this->warn('Proceeding with local deactivation only.');
            }

            // Clear local data
            $this->installationManager->deactivate();
            $this->licenseManager->clearCache();

            $this->info('✓ Installation deactivated. Application is now locked.');

        } catch (\Throwable $e) {
            $this->error('Deactivation error: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
