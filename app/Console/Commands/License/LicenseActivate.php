<?php

namespace App\Console\Commands\License;

use App\Exceptions\License\LicenseActivationException;
use App\Models\LicenseSecurityLog;
use App\Services\License\InstallationManager;
use App\Services\License\LicenseClient;
use App\Services\License\LicenseManager;
use App\Services\License\LicenseVerifier;
use Illuminate\Console\Command;

/**
 * Activates this LMS installation against the Softtrill license server.
 *
 * This command:
 * 1. Creates a cryptographically random installation identity (if not exists)
 * 2. Sends an HMAC-signed activation request to the license server
 * 3. Receives and verifies the signed license payload (Ed25519)
 * 4. Stores the signed payload and marks the installation as active
 *
 * Security: never exposes private keys or forges license data.
 * Only a valid response from the Softtrill license server creates a valid license.
 */
class LicenseActivate extends Command
{
    protected $signature = 'softtrill:license:activate
                            {--license-key= : Your Softtrill license key}
                            {--domain= : Domain to activate (defaults to APP_URL)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Activate this Softtrill LMS installation with the license server.';

    public function __construct(
        private readonly InstallationManager $installationManager,
        private readonly LicenseClient       $licenseClient,
        private readonly LicenseManager      $licenseManager,
        private readonly LicenseVerifier     $verifier,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════════════════════╗');
        $this->info('  ║   Softtrill LMS — License Activation              ║');
        $this->info('  ╚══════════════════════════════════════════════════╝');
        $this->info('');

        // 1. Get license key
        $licenseKey = $this->option('license-key')
            ?? $this->secret('Enter your Softtrill license key:');

        if (empty(trim($licenseKey))) {
            $this->error('License key cannot be empty.');
            return self::FAILURE;
        }

        // 2. Determine domain
        $domain = $this->option('domain')
            ?? $this->verifier->normalizeDomain(config('app.url', ''));

        $this->line("  Domain : <info>{$domain}</info>");
        $this->info('');

        if (!$this->option('force') && !$this->confirm("Activate this installation for domain '{$domain}'?")) {
            $this->info('Activation cancelled.');
            return self::SUCCESS;
        }

        // 3. Check for existing installation
        try {
            $existing = $this->installationManager->getInstallation();
        } catch (\Throwable $e) {
            $this->warn('Existing installation record found but HMAC is invalid (possible tampering).');
            $this->warn('Proceeding with re-activation...');
            $existing = null;
        }

        if ($existing && $existing->status === 'active') {
            $this->warn('An active installation already exists.');
            $this->line('  Installation ID: ' . $existing->installation_id);
            if (!$this->option('force') && !$this->confirm('Re-activate? This will deactivate the current installation first.')) {
                return self::SUCCESS;
            }
            // Deactivate existing
            try {
                $this->licenseClient->deactivate(
                    $existing->installation_id,
                    $existing->api_credential,
                    're-activation'
                );
            } catch (\Throwable $e) {
                $this->warn('Could not notify server of deactivation: ' . $e->getMessage());
            }
            $this->installationManager->deactivate();
        }

        // 4. Create installation identity
        $this->line('  Generating installation identity...');
        try {
            $result       = $this->installationManager->createInstallation($domain);
            $installation = $result['installation'];
            $credential   = $result['plain_credential'];
        } catch (\Throwable $e) {
            $this->error('Failed to create installation: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line("  Installation ID: <info>{$installation->installation_id}</info>");
        $this->info('');

        // 5. Call license server
        $this->line('  Contacting Softtrill license server...');
        try {
            $response = $this->licenseClient->activate(
                licenseKey: $licenseKey,
                installationId: $installation->installation_id,
                domain: $domain,
                apiCredential: $credential
            );
        } catch (\Throwable $e) {
            $this->error('License server error: ' . $e->getMessage());
            // Clean up pending installation
            $this->installationManager->deactivate();
            return self::FAILURE;
        }

        // 6. Verify the signed payload
        $signedPayload = $response['signed_payload'] ?? null;
        if (empty($signedPayload)) {
            $this->error('License server returned no signed payload.');
            $this->installationManager->deactivate();
            return self::FAILURE;
        }

        try {
            $this->line('  Verifying license signature (Ed25519)...');
            $this->licenseManager->storeSignedPayload($installation, $signedPayload);
            $this->installationManager->markActivated($installation);
        } catch (\Throwable $e) {
            $this->error('Signature verification failed: ' . $e->getMessage());
            $this->error('The license server response was invalid. Contact Softtrill support.');
            $this->installationManager->deactivate();
            return self::FAILURE;
        }

        LicenseSecurityLog::record(
            LicenseSecurityLog::EVENT_ACTIVATION_SUCCESS,
            LicenseSecurityLog::SEVERITY_INFO,
            ['domain' => $domain],
            $installation->installation_id
        );

        $this->info('');
        $this->info('  ✓ License activated successfully!');
        $this->info('');
        $this->line("  Status : <info>active</info>");
        $this->line("  Domain : <info>{$domain}</info>");
        $this->info('');

        return self::SUCCESS;
    }
}
