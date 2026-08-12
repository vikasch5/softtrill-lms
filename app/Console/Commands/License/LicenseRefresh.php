<?php

namespace App\Console\Commands\License;

use App\Services\License\InstallationManager;
use App\Services\License\LicenseClient;
use App\Services\License\LicenseManager;
use Illuminate\Console\Command;

/**
 * Force a fresh license validation against the Softtrill license server.
 * Updates the locally cached signed payload with a fresh one.
 */
class LicenseRefresh extends Command
{
    protected $signature = 'softtrill:license:refresh';
    protected $description = 'Force a fresh license validation against the Softtrill license server.';

    public function __construct(
        private readonly LicenseManager      $licenseManager,
        private readonly LicenseClient       $licenseClient,
        private readonly InstallationManager $installationManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Contacting Softtrill license server...');

        try {
            $installation = $this->installationManager->getInstallation();

            if ($installation === null || $installation->status !== 'active') {
                $this->error('No active installation. Run: php artisan softtrill:license:activate');
                return self::FAILURE;
            }

            $response = $this->licenseClient->validate(
                installationId: $installation->installation_id,
                apiCredential: $installation->api_credential,
                domain: $installation->domain
            );

            $signedPayload = $response['signed_payload'] ?? null;
            if (empty($signedPayload)) {
                $this->error('License server returned no signed payload.');
                return self::FAILURE;
            }

            $this->licenseManager->storeSignedPayload($installation, $signedPayload);
            $this->licenseManager->clearCache();
            $this->installationManager->touchValidated($installation);

            // Clear grace period on successful refresh
            $installation->update(['grace_expires_at' => null]);

            $this->info('✓ License refreshed successfully. New signed payload stored.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('License refresh failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
