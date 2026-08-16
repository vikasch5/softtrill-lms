<?php

namespace App\Console\Commands\License;

use Illuminate\Console\Command;

/**
 * GenerateManifest — generates a softtrill.manifest.json for tamper detection.
 *
 * This command is run on YOUR development/build machine (not the customer server).
 * It produces a manifest of SHA-256 hashes for critical files.
 * You then sign the manifest with your Ed25519 private key and include the
 * signed manifest in the distribution package.
 *
 * Usage (on your build server):
 *   php artisan softtrill:license:generate-manifest
 *
 * The output manifest (softtrill.manifest.json) is then signed with the private key
 * using the companion signing tool on the license server. Never do this on customer servers.
 *
 * IMPORTANT: The private key is NEVER used by this command.
 * This command only produces the unsigned manifest.
 * Signing is done separately on the license server.
 */
class GenerateManifest extends Command
{
    protected $signature = 'softtrill:license:generate-manifest
                            {--release= : Release version string}
                            {--output= : Output path (default: base_path/softtrill.manifest.json)}
                            {--auto-sign : Automatically sign the manifest using the License Server}
                            {--token= : The authentication token for the license server}';

    protected $description = 'Generate an unsigned file manifest for tamper detection (sign this on the license server).';

    /**
     * Critical files to include in the tamper detection manifest.
     * Add any file whose modification would bypass or weaken license enforcement.
     */
    private const PROTECTED_FILES = [
        // Core license services
        'app/Services/License/LicenseVerifier.php',
        'app/Services/License/LicenseManager.php',
        'app/Services/License/LicenseClient.php',
        'app/Services/License/EntitlementManager.php',
        'app/Services/License/InstallationManager.php',
        'app/Services/License/TamperDetector.php',

        // Middleware
        'app/Http/Middleware/CheckLicense.php',
        'app/Http/Middleware/RequireFeature.php',

        // Providers
        'app/Providers/AppServiceProvider.php',

        // Models
        'app/Models/LicenseInstallation.php',
        'app/Models/LicenseEntitlement.php',
        'app/Models/LicenseSecurityLog.php',

        // Bootstrap (middleware registration)
        'bootstrap/app.php',

        // Config
        'config/license.php',
    ];

    public function handle(): int
    {
        $version = $this->option('release') ?? 'dev-' . date('Ymd');
        $output  = $this->option('output') ?? base_path('softtrill.manifest.json');

        $this->info("Generating manifest for version: {$version}");
        $this->line('');

        $files  = [];
        $errors = [];

        foreach (self::PROTECTED_FILES as $relativePath) {
            $fullPath = base_path($relativePath);

            if (!file_exists($fullPath)) {
                $errors[] = $relativePath;
                $this->warn("  MISSING: {$relativePath}");
                continue;
            }

            $hash          = hash_file('sha256', $fullPath);
            $files[$relativePath] = $hash;
            $this->line("  ✓ {$relativePath}");
        }

        if (!empty($errors)) {
            $this->error('');
            $this->error('The following files were not found:');
            foreach ($errors as $f) {
                $this->line("  - {$f}");
            }
        }

        $manifest = [
            'manifest_version' => 1,
            'release_version'  => $version,
            'release_id'       => \Illuminate\Support\Str::uuid()->toString(),
            'product'          => config('license.product', 'softtrill-lms'),
            'generated_at'     => now()->toISOString(),
            'algorithm'        => 'sha256',
            'key_version'      => 1, // Phase 8 & 9 prep
            'protected_files'  => array_keys($files),
            'files'            => $files,
            // 'signature' field is NOT included here — it must be added by the
            // license server's signing tool using the Ed25519 private key.
        ];

        // Ensure canonical sorting before writing
        ksort($manifest);
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($output, $json);

        if ($this->option('auto-sign')) {
            $this->info("Attempting to auto-sign via License Server...");
            
            $serverUrl = rtrim(env('LICENSE_SERVER_URL'), '/');
            $secret = $this->option('token');
            
            if (empty($serverUrl)) {
                $this->error("Auto-sign failed: LICENSE_SERVER_URL is missing in .env");
                return self::FAILURE;
            }

            if (empty($secret)) {
                $this->error("Auto-sign failed: You must provide the --token=YOUR_SECRET flag to authenticate with the License Server.");
                return self::FAILURE;
            }

            try {
                $response = \Illuminate\Support\Facades\Http::withToken($secret)
                    ->post("{$serverUrl}/api/manifest/sign", [
                        'manifest' => $manifest
                    ]);
                
                if ($response->successful() && $response->json('success')) {
                    $signedManifest = $response->json('signed_manifest');
                    $signedJson = json_encode($signedManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    file_put_contents($output, $signedJson);
                    
                    $this->info('');
                    $this->info("✓ Manifest successfully automatically signed by License Server: {$output}");
                } else {
                    $this->error("Failed to sign manifest: " . $response->body());
                    return self::FAILURE;
                }
            } catch (\Exception $e) {
                $this->error("Error communicating with License Server: " . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->info('');
            $this->info("✓ Unsigned manifest written to: {$output}");
            $this->warn('');
            $this->warn('NEXT STEP: Sign this manifest on your Softtrill license server with:');
            $this->warn('  php artisan softtrill:license:sign-manifest /path/to/softtrill.manifest.json');
            $this->warn('Or pass the --auto-sign flag to sign automatically.');
        }

        return self::SUCCESS;
    }
}
