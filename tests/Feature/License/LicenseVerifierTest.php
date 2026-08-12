<?php

namespace Tests\Feature\License;

use App\Exceptions\License\LicenseTamperedException;
use App\Services\License\LicenseVerifier;

/**
 * Tests for the Ed25519 signature verification layer.
 *
 * Adversarial scenarios tested:
 * - Modifying any field in the payload must break the signature
 * - Wrong installation_id must be detected
 * - Wrong domain must be detected
 * - Wrong product must be detected
 * - Expired payload must be reported (separate from signature)
 * - Truncated payload must be rejected
 * - Invalid base64 must be rejected
 * - Reusing a valid payload for a different installation must fail
 */
class LicenseVerifierTest extends LicenseTestCase
{
    private LicenseVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = app(LicenseVerifier::class);
    }

    // -----------------------------------------------------------------------
    // Valid signatures
    // -----------------------------------------------------------------------

    public function test_valid_payload_passes_verification(): void
    {
        $payload = $this->validPayload();
        $signed  = $this->signPayload($payload);

        $verified = $this->verifier->verifySignedPayload(
            $signed,
            $this->testInstallationId,
            $this->testDomain
        );

        $this->assertEquals('active', $verified['status']);
        $this->assertEquals(100, $verified['max_users']);
        $this->assertEquals($this->testInstallationId, $verified['installation_id']);
    }

    // -----------------------------------------------------------------------
    // Tampered payload fields
    // -----------------------------------------------------------------------

    public function test_tampered_max_users_fails_verification(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload(['max_users' => 100]);
        $signed  = $this->signPayload($payload);

        // Decode and modify the payload in the signed blob
        $raw      = base64_decode($signed);
        $sig      = substr($raw, 0, 64);
        $json     = substr($raw, 64);
        $modified = str_replace('"max_users":100', '"max_users":99999', $json);
        $tampered = base64_encode($sig . $modified);

        $this->verifier->verifySignedPayload($tampered, $this->testInstallationId, $this->testDomain);
    }

    public function test_tampered_expires_at_fails_verification(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload(['expires_at' => now()->addYear()->toISOString()]);
        $signed  = $this->signPayload($payload);

        $raw      = base64_decode($signed);
        $sig      = substr($raw, 0, 64);
        $json     = substr($raw, 64);
        $modified = preg_replace('/"expires_at":"[^"]*"/', '"expires_at":"2099-01-01T00:00:00Z"', $json);
        $tampered = base64_encode($sig . $modified);

        $this->verifier->verifySignedPayload($tampered, $this->testInstallationId, $this->testDomain);
    }

    public function test_tampered_status_fails_verification(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload(['status' => 'suspended']);
        $signed  = $this->signPayload($payload);

        $raw      = base64_decode($signed);
        $sig      = substr($raw, 0, 64);
        $json     = substr($raw, 64);
        $modified = str_replace('"status":"suspended"', '"status":"active"', $json);
        $tampered = base64_encode($sig . $modified);

        $this->verifier->verifySignedPayload($tampered, $this->testInstallationId, $this->testDomain);
    }

    // -----------------------------------------------------------------------
    // Installation ID binding
    // -----------------------------------------------------------------------

    public function test_wrong_installation_id_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload(['installation_id' => $this->testInstallationId]);
        $signed  = $this->signPayload($payload);

        // Verify with a DIFFERENT installation_id
        $this->verifier->verifySignedPayload($signed, bin2hex(random_bytes(32)), $this->testDomain);
    }

    public function test_copied_license_to_different_installation_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $originalInstall = $this->testInstallationId;
        $stolenInstall   = bin2hex(random_bytes(32));

        // License was issued for originalInstall
        $payload = $this->validPayload(['installation_id' => $originalInstall]);
        $signed  = $this->signPayload($payload);

        // Trying to use it on stolenInstall
        $this->verifier->verifySignedPayload($signed, $stolenInstall, $this->testDomain);
    }

    // -----------------------------------------------------------------------
    // Domain binding
    // -----------------------------------------------------------------------

    public function test_wrong_domain_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload(['domain' => 'licensed.example.com']);
        $signed  = $this->signPayload($payload);

        $this->verifier->verifySignedPayload($signed, $this->testInstallationId, 'attacker.example.com');
    }

    public function test_domain_normalization_accepts_www_prefix(): void
    {
        $payload = $this->validPayload(['domain' => 'example.com']);
        $signed  = $this->signPayload($payload);

        // www.example.com should normalize to example.com
        $verified = $this->verifier->verifySignedPayload($signed, $this->testInstallationId, 'www.example.com');
        $this->assertEquals('active', $verified['status']);
    }

    // -----------------------------------------------------------------------
    // Product binding
    // -----------------------------------------------------------------------

    public function test_wrong_product_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload(['product' => 'other-product']);
        $signed  = $this->signPayload($payload);

        $this->verifier->verifySignedPayload($signed, $this->testInstallationId, $this->testDomain);
    }

    // -----------------------------------------------------------------------
    // Structural validation
    // -----------------------------------------------------------------------

    public function test_missing_required_field_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload();
        unset($payload['max_users']);
        $signed = $this->signPayload($payload);

        $this->verifier->verifySignedPayload($signed, $this->testInstallationId, $this->testDomain);
    }

    public function test_invalid_base64_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);
        $this->verifier->verifySignedPayload('!!!not-valid-base64!!!', $this->testInstallationId, $this->testDomain);
    }

    public function test_truncated_payload_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);
        $this->verifier->verifySignedPayload(base64_encode('short'), $this->testInstallationId, $this->testDomain);
    }

    public function test_wrong_key_id_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $payload = $this->validPayload(['key_id' => 'wrong-key-id']);
        $signed  = $this->signPayload($payload);

        $this->verifier->verifySignedPayload($signed, $this->testInstallationId, $this->testDomain);
    }

    // -----------------------------------------------------------------------
    // Domain normalization helpers
    // -----------------------------------------------------------------------

    public function test_domain_normalization(): void
    {
        $cases = [
            'https://www.example.com/path?q=1' => 'example.com',
            'http://example.com:8080'           => 'example.com',
            'WWW.EXAMPLE.COM'                   => 'example.com',
            'example.com'                       => 'example.com',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertEquals($expected, $this->verifier->normalizeDomain($input), "Input: {$input}");
        }
    }
}
