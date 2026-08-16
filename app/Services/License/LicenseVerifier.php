<?php

namespace App\Services\License;

use App\Exceptions\License\LicenseTamperedException;
use Carbon\Carbon;

use App\Services\License\Contracts\LicenseVerificationProviderInterface;

/**
 * LicenseVerifier — pure cryptographic verification layer.
 *
 * This class has NO side-effects. It does not talk to the database,
 * does not cache, and does not log. It receives raw bytes and returns
 * a verified array or throws a LicenseTamperedException.
 *
 * All values used for entitlement decisions MUST pass through this class.
 * Do not trust any value that has not been verified by verifySignedPayload().
 *
 * Algorithm: Ed25519 (via Verification Provider).
 *
 * Signed payload format:
 *   base64( 64_byte_ed25519_signature + utf8_canonical_json_payload )
 *
 * Canonical JSON: keys sorted alphabetically, no extra whitespace.
 */
final class LicenseVerifier
{
    /** Ed25519 signature length in bytes */
    private const SIGNATURE_BYTES = 64;

    /** Minimum expected fields in a valid license payload */
    private const REQUIRED_FIELDS = [
        'schema_version',
        'license_id',
        'customer_id',
        'product',
        'installation_id',
        'domain',
        'status',
        'revocation_status',
        'issued_at',
        'server_time',
        'expires_at',
        'grace_until',
        'max_users',
        'key_version',
        'key_id',
        'entitlement_version',
    ];

    public function __construct(
        private readonly string $publicKeyBase64,
        private readonly string $expectedKeyId,
        private readonly string $expectedProduct,
        private readonly LicenseVerificationProviderInterface $verificationProvider,
    ) {}

    /**
     * Verify the signed payload and return the decoded, verified license data.
     *
     * @param string $signedPayloadBase64 base64(signature + canonical_json)
     * @param string $expectedInstallationId installation_id to bind against
     * @param string $expectedDomain normalized domain to bind against
     * @return array verified license payload
     *
     * @throws LicenseTamperedException on any verification failure
     */
    public function verifySignedPayload(
        string $signedPayloadBase64,
        string $expectedInstallationId,
        string $expectedDomain
    ): array {
        // 1. Decode the public key
        $publicKey = $this->decodePublicKey();

        // 2. Decode the combined blob
        $raw = base64_decode($signedPayloadBase64, strict: true);
        if ($raw === false) {
            throw new LicenseTamperedException('Signed payload is not valid base64.');
        }

        if (strlen($raw) <= self::SIGNATURE_BYTES) {
            throw new LicenseTamperedException('Signed payload is too short to contain a signature and payload.');
        }

        // 3. Split signature and message
        $signature   = substr($raw, 0, self::SIGNATURE_BYTES);
        $messageJson = substr($raw, self::SIGNATURE_BYTES);

        // 4. Verify Ed25519 signature via native/php provider
        try {
            $valid = $this->verificationProvider->verifyEd25519($messageJson, $signature, $publicKey);
        } catch (\Exception $e) {
            throw new LicenseTamperedException('Signature verification error: ' . $e->getMessage());
        }

        if (!$valid) {
            throw new LicenseTamperedException('Ed25519 signature verification failed. Payload has been tampered with.');
        }

        // 5. Decode the JSON payload
        $payload = json_decode($messageJson, associative: true, flags: JSON_THROW_ON_ERROR);

        // 6. Validate required fields exist
        $this->assertRequiredFields($payload);

        // 7. Validate key_id
        if (!hash_equals($this->expectedKeyId, (string) ($payload['key_id'] ?? ''))) {
            throw new LicenseTamperedException('License key_id mismatch. Possible key rotation issue or tampering.');
        }

        // 8. Validate product
        if (!hash_equals($this->expectedProduct, (string) ($payload['product'] ?? ''))) {
            throw new LicenseTamperedException('License product mismatch. This license is not for softtrill-lms.');
        }

        // 9. Validate installation_id binding
        if (!hash_equals($expectedInstallationId, (string) ($payload['installation_id'] ?? ''))) {
            throw new LicenseTamperedException('License installation_id does not match this installation. License may have been copied.');
        }

        // 10. Validate domain binding
        $normalizedPayloadDomain = $this->normalizeDomain((string) ($payload['domain'] ?? ''));
        $normalizedExpectedDomain = $this->normalizeDomain($expectedDomain);
        if (!hash_equals($normalizedPayloadDomain, $normalizedExpectedDomain)) {
            throw new LicenseTamperedException('License domain does not match this installation domain.');
        }

        // 11. Validate schema version
        if (($payload['schema_version'] ?? 0) < 1) {
            throw new LicenseTamperedException('Unsupported license schema version.');
        }

        return $payload;
    }

    /**
     * Verify only the signature and structural integrity (no binding checks).
     * Used for pre-checks before binding validation.
     *
     * @param string $signedPayloadBase64
     * @return array decoded payload (not yet binding-checked)
     * @throws LicenseTamperedException
     */
    public function verifySignatureOnly(string $signedPayloadBase64): array
    {
        $publicKey = $this->decodePublicKey();

        $raw = base64_decode($signedPayloadBase64, strict: true);
        if ($raw === false || strlen($raw) <= self::SIGNATURE_BYTES) {
            throw new LicenseTamperedException('Signed payload is invalid.');
        }

        $signature   = substr($raw, 0, self::SIGNATURE_BYTES);
        $messageJson = substr($raw, self::SIGNATURE_BYTES);

        try {
            $valid = $this->verificationProvider->verifyEd25519($messageJson, $signature, $publicKey);
        } catch (\Exception $e) {
            throw new LicenseTamperedException('Signature error: ' . $e->getMessage());
        }

        if (!$valid) {
            throw new LicenseTamperedException('Ed25519 signature verification failed.');
        }

        return json_decode($messageJson, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Produce a canonical JSON string suitable for signing.
     * Keys are sorted alphabetically. No extra whitespace.
     *
     * This ensures the server and client produce identical byte sequences
     * for the same logical payload.
     */
    public function canonicalize(array $payload): string
    {
        ksort($payload);
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Compute SHA-256 of the canonical JSON payload (without signature).
     * Used as a fast pre-check before running full Ed25519 verification.
     */
    public function payloadHash(string $signedPayloadBase64): string
    {
        $raw = base64_decode($signedPayloadBase64, strict: true);
        if ($raw === false || strlen($raw) <= self::SIGNATURE_BYTES) {
            return '';
        }
        $messageJson = substr($raw, self::SIGNATURE_BYTES);
        return hash('sha256', $messageJson);
    }

    /**
     * Normalize a domain for comparison:
     * - lowercase
     * - strip scheme (http://, https://)
     * - strip www prefix
     * - strip trailing slash and path
     * - strip port (configurable exception for localhost)
     */
    public function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = explode('/', $domain)[0];       // strip path
        $domain = explode('?', $domain)[0];       // strip query
        $domain = preg_replace('#:\d+$#', '', $domain); // strip port
        return $domain;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function decodePublicKey(): string
    {
        $key = base64_decode($this->publicKeyBase64, strict: true);
        if ($key === false || strlen($key) !== \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new LicenseTamperedException(
                'Invalid Ed25519 public key. Expected ' . \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES . ' bytes.'
            );
        }
        return $key;
    }

    private function assertRequiredFields(array $payload): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new LicenseTamperedException("License payload missing required field: {$field}");
            }
        }
    }
}
