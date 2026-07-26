<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EncryptSourceCode extends Command
{
    /**
     * The name and signature of the console command.
     * Usage: php artisan app:encrypt --key=YOUR_SECRET_KEY
     *
     * @var string
     */
    protected $signature = 'app:encrypt
                            {--key= : Your secret encryption key (keep this safe!)}
                            {--force : Skip confirmation prompt}
                            {--dir=* : Directories to encrypt (default: app, routes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypts PHP source code using AES-256-CBC. Only decryptable with YOUR secret key.';

    /** Files to skip during encryption (must not be encrypted to keep app functional) */
    private array $skipFiles = [
        'EncryptSourceCode.php',
        'CheckLicense.php',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('=========================================================');
        $this->warn('  AES-256 SOURCE CODE ENCRYPTER — Softtrill LMS');
        $this->warn('=========================================================');
        $this->line('');

        // 1. Get and validate the secret key
        $secretKey = $this->option('key');
        if (empty($secretKey)) {
            $secretKey = $this->secret('Enter your secret encryption key (keep this private):');
        }

        if (empty($secretKey)) {
            $this->error('Secret key cannot be empty!');
            return 1;
        }

        $this->line('');
        $this->warn('CRITICAL WARNING: NEVER RUN THIS ON YOUR DEVELOPMENT FOLDER!');
        $this->warn('Only run this on a COPY of the project you are handing to the client.');
        $this->line('');

        if (!$this->option('force') && !$this->confirm('Have you backed up your code? Are you ready to encrypt?')) {
            $this->info('Encryption aborted. No files were changed.');
            return 0;
        }

        // 2. Derive a strong 256-bit key from the passphrase using PBKDF2
        $derivedKey = hash_pbkdf2('sha256', $secretKey, 'softtrill-lms-salt', 10000, 32, true);

        $directories = $this->option('dir');
        if (empty($directories)) {
            $directories = ['app', 'routes'];
        }

        $totalFiles     = 0;
        $encryptedFiles = 0;
        $skippedFiles   = 0;

        foreach ($directories as $dir) {
            $path = base_path($dir);

            if (!File::isDirectory($path)) {
                $this->error("Directory does not exist: {$dir}");
                continue;
            }

            $files = File::allFiles($path);
            $this->info("Scanning directory: {$dir} (" . count($files) . " files found)...");

            $this->withProgressBar($files, function ($file) use ($derivedKey, &$totalFiles, &$encryptedFiles, &$skippedFiles) {
                $totalFiles++;

                if ($file->getExtension() !== 'php') {
                    $skippedFiles++;
                    return;
                }

                if (in_array($file->getFilename(), $this->skipFiles)) {
                    $skippedFiles++;
                    return;
                }

                $this->encryptFile($file->getRealPath(), $derivedKey);
                $encryptedFiles++;
            });

            $this->newLine(2);
        }

        $this->info('Encryption complete!');
        $this->table(
            ['Total Files', 'Encrypted', 'Skipped'],
            [[$totalFiles, $encryptedFiles, $skippedFiles]]
        );

        $this->line('');
        $this->warn('IMPORTANT: Save your secret key in a safe place!');
        $this->warn('Without it, the encrypted code CANNOT be executed.');
        $this->warn('Add it to the .env of the client server as:');
        $this->warn('APP_SOURCE_KEY="' . $secretKey . '"');

        return 0;
    }

    /**
     * Encrypt a single PHP file using AES-256-CBC.
     *
     * The original file is replaced with a self-decrypting stub that:
     * - Reads APP_SOURCE_KEY from the environment
     * - Derives the AES key once using PBKDF2 (cached statically for performance)
     * - Decrypts the ciphertext IN MEMORY using openssl_decrypt()
     * - Executes the original code via eval() — plaintext never touches disk
     *
     * Without the correct APP_SOURCE_KEY, decryption fails and the app stops.
     */
    private function encryptFile(string $filePath, string $derivedKey): void
    {
        $originalCode = file_get_contents($filePath);

        // Skip already-encrypted files
        if (str_contains($originalCode, 'AES-256-ENC:SOFTTRILL')) {
            return;
        }

        // Skip empty files
        if (empty(trim($originalCode))) {
            return;
        }

        // Generate a unique random IV for each file
        $iv = random_bytes(16);

        // Encrypt the original PHP source code
        $ciphertext = openssl_encrypt(
            $originalCode,
            'aes-256-cbc',
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Store IV + ciphertext together, encoded as base64
        $payload = base64_encode($iv . $ciphertext);

        // Build the self-decrypting stub using string concatenation.
        // Heredoc is intentionally avoided here because escaped dollar signs
        // inside heredoc strings cause PHP parse errors.
        $stub  = '<?php /* AES-256-ENC:SOFTTRILL */' . "\n";
        $stub .= '(function(){' . "\n";
        $stub .= 'static $k=null;' . "\n";
        $stub .= 'if($k===null){' . "\n";
        $stub .= '$s=getenv(\'APP_SOURCE_KEY\')?:\'\';' . "\n";
        $stub .= 'if(empty($s)){http_response_code(403);die(\'[Softtrill LMS] APP_SOURCE_KEY not set.\');}' . "\n";
        $stub .= '$k=hash_pbkdf2(\'sha256\',$s,\'softtrill-lms-salt\',10000,32,true);' . "\n";
        $stub .= '}' . "\n";
        $stub .= '$d=base64_decode(\'' . $payload . '\');' . "\n";
        $stub .= '$iv=substr($d,0,16);$c=substr($d,16);' . "\n";
        $stub .= '$code=openssl_decrypt($c,\'aes-256-cbc\',$k,OPENSSL_RAW_DATA,$iv);' . "\n";
        $stub .= 'if($code===false){http_response_code(403);die(\'[Softtrill LMS] Invalid key.\');}' . "\n";
        $stub .= 'eval(\'?>\'.$code);' . "\n";
        $stub .= '})();' . "\n";

        file_put_contents($filePath, $stub);
    }
}
