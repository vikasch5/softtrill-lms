<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DecryptSourceCode extends Command
{
    /**
     * The name and signature of the console command.
     * Usage: php artisan app:decrypt --key=YOUR_SECRET_KEY
     *
     * @var string
     */
    protected $signature = 'app:decrypt
                            {--key= : The same secret key used during encryption}
                            {--force : Skip confirmation prompt}
                            {--dir=* : Directories to decrypt (default: app, routes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypts PHP source code previously encrypted by app:encrypt. Requires the original secret key.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('=========================================================');
        $this->warn('  AES-256 SOURCE CODE DECRYPTER — Softtrill LMS');
        $this->warn('=========================================================');
        $this->line('');

        // 1. Get the secret key
        $secretKey = $this->option('key');
        if (empty($secretKey)) {
            $secretKey = $this->secret('Enter the secret key that was used to encrypt:');
        }

        if (empty($secretKey)) {
            $this->error('Secret key cannot be empty!');
            return 1;
        }

        $this->line('');
        if (!$this->option('force') && !$this->confirm('This will overwrite the encrypted files with decrypted code. Continue?')) {
            $this->info('Decryption aborted. No files were changed.');
            return 0;
        }

        // 2. Derive the same 256-bit key using the same PBKDF2 parameters
        //    that were used during encryption (MUST match exactly)
        $this->line('Deriving decryption key... (this may take a moment)');
        $derivedKey = hash_pbkdf2('sha256', $secretKey, 'softtrill-lms-salt', 10000, 32, true);
        $this->info('Key derived successfully.');
        $this->line('');

        $directories = $this->option('dir');
        if (empty($directories)) {
            $directories = ['app', 'routes'];
        }

        $totalFiles     = 0;
        $decryptedFiles = 0;
        $skippedFiles   = 0;
        $failedFiles    = [];

        foreach ($directories as $dir) {
            $path = base_path($dir);

            if (!File::isDirectory($path)) {
                $this->error("Directory does not exist: {$dir}");
                continue;
            }

            $files = File::allFiles($path);
            $this->info("Scanning directory: {$dir} (" . count($files) . " files found)...");

            $this->withProgressBar($files, function ($file) use ($derivedKey, &$totalFiles, &$decryptedFiles, &$skippedFiles, &$failedFiles) {
                $totalFiles++;

                if ($file->getExtension() !== 'php') {
                    $skippedFiles++;
                    return;
                }

                $content = file_get_contents($file->getRealPath());

                // Only process files that were encrypted by our tool
                if (!str_contains($content, 'AES-256-ENC:SOFTTRILL')) {
                    $skippedFiles++;
                    return;
                }

                $result = $this->decryptFile($file->getRealPath(), $content, $derivedKey);

                if ($result === true) {
                    $decryptedFiles++;
                } else {
                    $failedFiles[] = $file->getRelativePathname();
                }
            });

            $this->newLine(2);
        }

        $this->info('Decryption complete!');
        $this->table(
            ['Total Files', 'Decrypted', 'Skipped (not encrypted)', 'Failed'],
            [[$totalFiles, $decryptedFiles, $skippedFiles, count($failedFiles)]]
        );

        if (!empty($failedFiles)) {
            $this->error('The following files failed to decrypt (wrong key or corrupted):');
            foreach ($failedFiles as $f) {
                $this->line('  - ' . $f);
            }
            return 1;
        }

        $this->line('');
        $this->info('All files have been restored to their original PHP source code!');

        return 0;
    }

    /**
     * Decrypt a single encrypted PHP file and restore the original source code.
     *
     * @return bool  true on success, false on failure
     */
    private function decryptFile(string $filePath, string $content, string $derivedKey): bool
    {
        // Extract the base64 payload from the stub using regex.
        // The stub line looks like:
        //   $d=base64_decode('PAYLOAD_HERE');
        if (!preg_match('/\$d=base64_decode\(\'([A-Za-z0-9+\/=]+)\'\);/', $content, $matches)) {
            return false;
        }

        $encodedPayload = $matches[1];

        // Decode the base64 payload to get IV + ciphertext
        $rawData = base64_decode($encodedPayload);

        if ($rawData === false || strlen($rawData) < 16) {
            return false;
        }

        // Split: first 16 bytes = IV, rest = ciphertext
        $iv         = substr($rawData, 0, 16);
        $ciphertext = substr($rawData, 16);

        // Decrypt the original PHP source code
        $originalCode = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($originalCode === false) {
            // Decryption failed — wrong key or corrupted data
            return false;
        }

        // Write the original PHP code back to the file
        file_put_contents($filePath, $originalCode);

        return true;
    }
}
