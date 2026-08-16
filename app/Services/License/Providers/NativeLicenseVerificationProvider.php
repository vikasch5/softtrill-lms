<?php

namespace App\Services\License\Providers;

use App\Services\License\Contracts\LicenseVerificationProviderInterface;

class NativeLicenseVerificationProvider implements LicenseVerificationProviderInterface
{
    /**
     * @throws \Exception if the native extension is missing.
     */
    public function verifyEd25519(string $message, string $signature, string $publicKey): bool
    {
        if (!extension_loaded('softtrill_license')) {
            throw new \Exception('Native license verification extension (softtrill_license) is not loaded. License cannot be verified securely.');
        }

        if (!function_exists('softtrill_verify_ed25519')) {
            throw new \Exception('Native license verification function (softtrill_verify_ed25519) is missing. The extension may be corrupt.');
        }

        return \softtrill_verify_ed25519($message, $signature, $publicKey);
    }
}
