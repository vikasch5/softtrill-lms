<?php

namespace Tests\Feature\License;

use App\Models\LicenseEntitlement;
use App\Models\LicenseInstallation;
use App\Services\License\LicenseVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base test class for license tests.
 *
 * Provides helpers to create test Ed25519 keypairs and sign test payloads.
 * Uses a test keypair — NOT the real Softtrill private key.
 */
abstract class LicenseTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $testPublicKey;
    protected string $testPrivateKey;
    protected string $testInstallationId;
    protected string $testDomain;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate a fresh Ed25519 keypair for each test
        $keypair            = sodium_crypto_sign_keypair();
        $this->testPrivateKey = sodium_crypto_sign_secretkey($keypair);
        $this->testPublicKey  = base64_encode(sodium_crypto_sign_publickey($keypair));
        $this->testInstallationId = bin2hex(random_bytes(32));
        $this->testDomain    = 'test.example.com';

        // Override config with test public key
        config(['license.public_key' => $this->testPublicKey]);
        config(['license.key_id'     => 'test-kid-001']);
        config(['license.product'    => 'softtrill-lms']);

        // Rebind LicenseVerifier with test key
        $this->app->singleton(LicenseVerifier::class, fn() => new LicenseVerifier(
            publicKeyBase64: $this->testPublicKey,
            expectedKeyId:   'test-kid-001',
            expectedProduct: 'softtrill-lms',
        ));
    }

    /**
     * Sign a payload with the test private key.
     * Returns base64(signature + canonical_json)
     */
    protected function signPayload(array $payload): string
    {
        ksort($payload);
        $json      = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $signature = sodium_crypto_sign_detached($json, $this->testPrivateKey);
        return base64_encode($signature . $json);
    }

    /**
     * Build a standard valid payload for the test installation.
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'license_id'      => 'test-license-' . uniqid(),
            'customer_id'     => 'test-customer-001',
            'product'         => 'softtrill-lms',
            'installation_id' => $this->testInstallationId,
            'domain'          => $this->testDomain,
            'status'          => 'active',
            'issued_at'       => now()->toISOString(),
            'expires_at'      => now()->addYear()->toISOString(),
            'max_users'       => 100,
            'features'        => ['dialer' => true, 'export' => true],
            'version'         => '1.0',
            'license_version' => 1,
            'key_id'          => 'test-kid-001',
        ], $overrides);
    }

    /**
     * Create a test LicenseInstallation and LicenseEntitlement in the DB.
     */
    protected function createActiveInstallation(array $payloadOverrides = []): LicenseInstallation
    {
        // Create installation
        $appKey    = config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        $derivedKey = hash_hmac('sha256', 'softtrill-installation-v1', $appKey, true);
        $hmac       = hash_hmac('sha256', $this->testInstallationId, $derivedKey);

        $installation = LicenseInstallation::create([
            'installation_id'     => $this->testInstallationId,
            'installation_hmac'   => $hmac,
            'api_credential'      => bin2hex(random_bytes(32)),
            'api_credential_hash' => bcrypt('test'),
            'domain'              => $this->testDomain,
            'status'              => 'active',
            'activated_at'        => now(),
            'last_validated_at'   => now(),
        ]);

        // Sign and store entitlement
        $payload       = $this->validPayload($payloadOverrides);
        $signedPayload = $this->signPayload($payload);

        LicenseEntitlement::create([
            'installation_id'    => $this->testInstallationId,
            'signed_payload'     => $signedPayload,
            'payload_hash'       => hash('sha256', $signedPayload),
            'license_id'         => $payload['license_id'],
            'payload_issued_at'  => now(),
            'payload_expires_at' => now()->addYear(),
            'cached_until'       => now()->addDay(),
            'cached_status'      => 'active',
        ]);

        return $installation;
    }
}
