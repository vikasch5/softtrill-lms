<?php

namespace App\Services\License;

use App\Models\LicenseSecurityLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * LicenseClient — handles all HTTP communication with the Softtrill license server.
 *
 * Security design:
 * - Every request is HMAC-SHA256 signed using the installation's API credential
 * - Each request includes a timestamp and nonce to prevent replay
 * - TLS is enforced (verify: true) — never disabled
 * - The API credential is per-installation — compromising one does not affect others
 * - Credentials can be revoked server-side at any time
 *
 * Request signing format (HMAC-SHA256):
 *   message = SORT_KEYS_JSON({ ...body_params, _ts: timestamp, _nonce: nonce })
 *   signature = HMAC-SHA256(message, api_credential)
 *   header: X-Softtrill-Sig: hex(signature)
 *   header: X-Softtrill-Ts: timestamp
 *   header: X-Softtrill-Nonce: nonce
 *   header: X-Softtrill-Install: installation_id
 */
final class LicenseClient
{
    public function __construct(
        private readonly string $serverUrl,
        private readonly int    $timeout,
    ) {}

    /**
     * POST /api/v1/license/activate
     * Called once during installation activation.
     * Does NOT require an API credential (uses license key for initial auth).
     */
    public function activate(
        string $licenseKey,
        string $installationId,
        string $domain,
        string $apiCredential
    ): array {
        $body = [
            'license_key'     => $licenseKey,
            'installation_id' => $installationId,
            'domain'          => $domain,
            'product'         => config('license.product'),
            'php_version'     => PHP_VERSION,
            'api_credential'  => $apiCredential,
        ];

        // For activation, sign with the license key itself (credential not yet registered)
        return $this->sendSignedRequest(
            endpoint: '/api/v1/license/activate',
            body: $body,
            credential: $licenseKey,
            installationId: $installationId
        );
    }

    /**
     * POST /api/v1/license/validate
     * Periodic heartbeat to obtain a fresh signed license payload.
     */
    public function validate(
        string $installationId,
        string $apiCredential,
        string $domain,
        array  $telemetry = []
    ): array {
        $body = [
            'installation_id' => $installationId,
            'domain'          => $domain,
            'product'         => config('license.product'),
            'telemetry'       => $telemetry,
        ];

        return $this->sendSignedRequest(
            endpoint: '/api/v1/license/validate',
            body: $body,
            credential: $apiCredential,
            installationId: $installationId
        );
    }

    /**
     * POST /api/v1/license/heartbeat
     * Lightweight check — does NOT return a full signed payload.
     * Returns a short-lived signed status token.
     */
    public function heartbeat(
        string $installationId,
        string $apiCredential,
        array  $telemetry = []
    ): array {
        return $this->sendSignedRequest(
            endpoint: '/api/v1/license/heartbeat',
            body: [
                'installation_id' => $installationId,
                'telemetry'       => $telemetry,
            ],
            credential: $apiCredential,
            installationId: $installationId
        );
    }

    /**
     * POST /api/v1/license/deactivate
     * Deregisters this installation from the license.
     */
    public function deactivate(
        string $installationId,
        string $apiCredential,
        string $reason = 'manual'
    ): array {
        return $this->sendSignedRequest(
            endpoint: '/api/v1/license/deactivate',
            body: [
                'installation_id' => $installationId,
                'reason'          => $reason,
            ],
            credential: $apiCredential,
            installationId: $installationId
        );
    }

    /**
     * POST /api/v1/license/authorize
     * Request a short-lived signed authorization token for a critical operation.
     */
    public function authorize(
        string $installationId,
        string $apiCredential,
        string $operation,
        array  $context = []
    ): array {
        $body = [
            'installation_id' => $installationId,
            'operation'       => $operation,
            'context'         => $context,
        ];

        return $this->sendSignedRequest(
            endpoint: '/api/v1/license/authorize',
            body: $body,
            credential: $apiCredential,
            installationId: $installationId
        );
    }

    // -----------------------------------------------------------------------
    // Private: signed request
    // -----------------------------------------------------------------------

    /**
     * Send an HMAC-signed POST request to the license server.
     *
     * @throws RuntimeException on network error or non-2xx response
     */
    private function sendSignedRequest(
        string $endpoint,
        array  $body,
        string $credential,
        string $installationId
    ): array {
        $timestamp = (string) time();
        $nonce     = bin2hex(random_bytes(16));

        // Add signing metadata to body (server verifies these)
        $body['_ts']    = $timestamp;
        $body['_nonce'] = $nonce;

        // Canonical body for signing: keys sorted
        ksort($body);
        $canonicalBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        // HMAC-SHA256 of canonical body with API credential as key
        $signature = hash_hmac('sha256', $canonicalBody, $credential);

        $url = rtrim($this->serverUrl, '/') . $endpoint;

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Softtrill-Sig'                => $signature,
                    'X-Softtrill-Ts'                 => $timestamp,
                    'X-Softtrill-Nonce'              => $nonce,
                    'X-Softtrill-Install'            => $installationId,
                    'X-Softtrill-Install-Credential' => $credential,
                    'Accept'                         => 'application/json',
                    'Content-Type'                   => 'application/json',
                ])
                ->post($url, $body);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[LicenseClient] Connection failed to license server', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            throw new RuntimeException('License server is unreachable: ' . $e->getMessage(), 0, $e);
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $status  = $response->status();
        $message = $response->json('message') ?? $response->body();

        Log::warning('[LicenseClient] License server error response', [
            'full_url'    => $url,
            'server_url'  => $this->serverUrl,
            'endpoint'    => $endpoint,
            'http_status' => $status,
            'body'        => substr($message, 0, 500),
        ]);

        throw new RuntimeException(
            "License server returned HTTP {$status}: " . substr($message, 0, 200)
        );
    }
}
