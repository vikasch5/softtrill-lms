<?php

namespace App\Services\License\Contracts;

interface LicenseVerificationProviderInterface
{
    /**
     * Verify an Ed25519 detached signature.
     *
     * @param string $message The canonical JSON payload
     * @param string $signature The 64-byte raw binary signature
     * @param string $publicKey The raw binary public key
     * @return bool True if valid, false otherwise
     */
    public function verifyEd25519(string $message, string $signature, string $publicKey): bool;
}
