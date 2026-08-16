<?php

namespace App\Services\License;

use App\Models\LicenseSecurityLog;
use Illuminate\Support\Facades\Log;

/**
 * TamperDetector — verifies integrity of critical application files.
 *
 * Reads a signed manifest (softtrill.manifest.json) that contains:
 * - SHA-256 hashes of critical files
 * - The manifest itself signed with the Softtrill Ed25519 private key
 *
 * The LMS verifies the manifest signature, then checks file hashes.
 *
 * Important limitations (honest):
 * - A root-level attacker can modify this file too
 * - A developer can remove the call to checkIntegrity()
 * - This is a DETECTION and RESPONSE mechanism, not a prevention mechanism
 * - If tampering is detected: log it, invalidate cache, restrict operations
 * - Do NOT reveal which files are tampered in error messages to the user
 *
 * Manifest format (softtrill.manifest.json):
 * {
 *   "version": "1.0.0",
 *   "release_id": "uuid",
 *   "product": "softtrill-lms",
 *   "issued_at": "2026-08-12T00:00:00Z",
 *   "files": {
 *     "app/Services/License/LicenseVerifier.php": "sha256hex...",
 *     ...
 *   },
 *   "signature": "base64-ed25519-signature-of-canonical-json-without-signature"
 * }
 */
final class TamperDetector
{
    private bool $tamperDetected = false;
    private array $tamperedFiles = []; // populated internally, never exposed to user

    public function __construct(
        private readonly LicenseVerifier $verifier,
        private readonly ?string         $manifestPath,
    ) {}

    /**
     * Run integrity check. Returns true if all critical files are intact.
     * Logs and records security events if tampering is detected.
     * Never throws — detection is best-effort.
     */
    public function checkIntegrity(?string $installationId = null): bool
    {
        if (empty($this->manifestPath) || !file_exists($this->manifestPath)) {
            // A hacker might delete the manifest to bypass checks. We must block this.
            Log::warning('[TamperDetector] Manifest missing. Assuming tampered.');
            $this->tamperDetected = true;
            return false;
        }

        try {
            $manifestJson = file_get_contents($this->manifestPath);
            if ($manifestJson === false) {
                Log::warning('[TamperDetector] Cannot read manifest file.');
                return true;
            }

            $manifest = json_decode($manifestJson, associative: true, flags: JSON_THROW_ON_ERROR);

            // Verify manifest signature
            $this->verifyManifestSignature($manifest);

            // Check file hashes
            $violations = $this->checkFileHashes($manifest['files'] ?? []);

            if (!empty($violations)) {
                $this->tamperDetected = true;
                $this->tamperedFiles  = $violations;

                LicenseSecurityLog::record(
                    LicenseSecurityLog::EVENT_TAMPER_DETECTED,
                    LicenseSecurityLog::SEVERITY_CRITICAL,
                    [
                        'file_count' => count($violations),
                        'version'    => $manifest['version'] ?? 'unknown',
                        'release_id' => $manifest['release_id'] ?? 'unknown',
                        // Do NOT log which specific files are tampered in user-visible logs
                    ],
                    $installationId
                );

                Log::critical('[TamperDetector] File integrity violation detected.', [
                    // This goes to the secure log — NOT exposed to users
                    'tampered_count' => count($violations),
                    'files'          => $violations,
                ]);

                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::warning('[TamperDetector] Integrity check error: ' . $e->getMessage());
            // If the manifest is unsigned or corrupted, this is a tamper attempt!
            $this->tamperDetected = true;
            return false;
        }
    }

    /**
     * Returns true if tampering was detected on the last checkIntegrity() call.
     */
    public function isTampered(): bool
    {
        return $this->tamperDetected;
    }

    /**
     * Returns how many files were found to be tampered.
     * Does NOT return which files — don't expose this to end users.
     */
    public function tamperedFileCount(): int
    {
        return count($this->tamperedFiles);
    }

    // -----------------------------------------------------------------------
    // Private
    // -----------------------------------------------------------------------

    /**
     * Verify the manifest's Ed25519 signature.
     * The signature covers the canonical JSON of the manifest without the "signature" key.
     *
     * @throws \App\Exceptions\License\LicenseTamperedException
     */
    private function verifyManifestSignature(array $manifest): void
    {
        $signature = $manifest['signature'] ?? null;
        if (empty($signature)) {
            throw new \RuntimeException('Manifest has no signature.');
        }

        // Remove the signature field to reproduce what was signed
        $toVerify = $manifest;
        unset($toVerify['signature']);

        // Canonical JSON (sorted keys)
        $canonical      = $this->verifier->canonicalize($toVerify);
        $signatureBytes = base64_decode($signature, strict: true);

        if ($signatureBytes === false || strlen($signatureBytes) !== 64) {
            throw new \RuntimeException('Manifest signature is not valid base64 or wrong length.');
        }

        // Verify using public key
        $publicKeyBase64 = config('license.public_key');
        $publicKey       = base64_decode($publicKeyBase64, strict: true);

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new \RuntimeException('Invalid public key for manifest verification.');
        }

        if (!sodium_crypto_sign_verify_detached($signatureBytes, $canonical, $publicKey)) {
            throw new \RuntimeException('Manifest signature verification failed — manifest may have been tampered.');
        }
    }

    /**
     * Check each file's SHA-256 hash against the manifest.
     * Returns array of relative paths that failed verification.
     */
    private function checkFileHashes(array $files): array
    {
        $violations = [];

        foreach ($files as $relativePath => $expectedHash) {
            $fullPath = base_path($relativePath);

            if (!file_exists($fullPath)) {
                $violations[] = $relativePath;
                continue;
            }

            $actualHash = hash_file('sha256', $fullPath);
            if (!hash_equals($expectedHash, $actualHash)) {
                $violations[] = $relativePath;
            }
        }

        return $violations;
    }
}
