<?php

namespace App\Services\License\Providers;

use App\Services\License\Contracts\LicenseVerificationProviderInterface;

class PhpLicenseVerificationProvider implements LicenseVerificationProviderInterface
{
    /**
     * @throws \Exception if used in production when native is required.
     */
    public function verifyEd25519(string $message, string $signature, string $publicKey): bool
    {
        // Fail closed if this is running in production and native extension is required
        if (config('app.env') === 'production' && config('license.native_required', true)) {
            throw new \Exception('PHP verification provider cannot be used in production. Native extension is required.');
        }

        try {
            return sodium_crypto_sign_verify_detached($signature, $message, $publicKey);
        } catch (\SodiumException $e) {
            return false;
        }
    }
}
