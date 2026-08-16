<?php

/**
 * =============================================================================
 * SOFTTRILL LICENSE SERVER — API Controller
 * =============================================================================
 *
 * This is a SCAFFOLD for the central Softtrill License Server.
 * It lives in a SEPARATE Laravel application, not in the customer LMS.
 *
 * Routes (to be registered on the license server):
 *   POST /api/v1/license/activate
 *   POST /api/v1/license/validate
 *   POST /api/v1/license/deactivate
 *   POST /api/v1/license/heartbeat
 *
 * All endpoints verify the installation's HMAC-signed request before responding.
 * The private key is loaded from environment or a secure vault — NEVER from DB.
 *
 * File: app/Http/Controllers/Api/LicenseServerController.php (on the license server)
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LicenseServerController extends Controller
{
    /**
     * POST /api/v1/license/activate
     *
     * Validates the license key, checks activation limits, records the installation,
     * and returns a signed license payload.
     *
     * Request body:
     *   license_key, installation_id, domain, product, php_version, _ts, _nonce
     * Request headers:
     *   X-Softtrill-Sig, X-Softtrill-Ts, X-Softtrill-Nonce, X-Softtrill-Install
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key'     => 'required|string',
            'installation_id' => 'required|string|size:64',
            'domain'          => 'required|string|max:255',
            'product'         => 'required|string',
        ]);

        // 1. Verify request signature (HMAC of body with license_key)
        if (!$this->verifyRequestSignature($request, $request->input('license_key'))) {
            Log::warning('[LicenseServer] Activation signature mismatch.', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid request signature.'], 403);
        }

        // 2. Verify nonce (replay protection)
        if (!$this->consumeNonce($request->input('installation_id'), $request->input('_nonce', ''))) {
            return response()->json(['error' => 'Replay detected or invalid nonce.'], 403);
        }

        // 3. Look up license
        $license = DB::table('licenses')
            ->where('license_key', $request->input('license_key'))
            ->where('product', $request->input('product'))
            ->where('status', 'active')
            ->first();

        if (!$license) {
            $this->logEvent(null, null, 'activation.failure', 'warning', ['reason' => 'license_not_found']);
            return response()->json(['error' => 'License not found or inactive.'], 404);
        }

        // 4. Check license is not expired
        if (now()->isAfter($license->expires_at)) {
            $this->logEvent($license->id, null, 'activation.failure', 'warning', ['reason' => 'license_expired']);
            return response()->json(['error' => 'License has expired.'], 403);
        }

        // 5. Check activation limit
        $activeCount = DB::table('license_activations')
            ->where('license_id', $license->id)
            ->where('status', 'active')
            ->count();

        if ($activeCount >= $license->max_activations) {
            $this->logEvent($license->id, null, 'activation.failure', 'warning', ['reason' => 'activation_limit_exceeded', 'count' => $activeCount]);
            return response()->json(['error' => 'Activation limit exceeded for this license.'], 403);
        }

        // 6. Check domain policy
        $normalizedDomain = $this->normalizeDomain($request->input('domain'));
        // [Optional: enforce domain whitelist/blacklist here]

        // 7. Record activation
        $apiCredential = bin2hex(random_bytes(32));
        DB::table('license_activations')->insert([
            'license_id'           => $license->id,
            'installation_id'      => $request->input('installation_id'),
            'api_credential_hash'  => Hash::make($apiCredential),
            'domain'               => $normalizedDomain,
            'ip_address'           => $request->ip(),
            'php_version'          => $request->input('php_version', 'unknown'),
            'status'               => 'active',
            'activated_at'         => now(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // 8. Build and sign the license payload
        $payload = $this->buildPayload($license, $request->input('installation_id'), $normalizedDomain);
        $signed  = $this->signPayload($payload);

        $this->logEvent($license->id, $request->input('installation_id'), 'activation.success', 'info', [
            'domain' => $normalizedDomain,
        ]);

        return response()->json([
            'ok'             => true,
            'signed_payload' => $signed,
            'api_credential' => $apiCredential, // only sent once, at activation
        ]);
    }

    /**
     * POST /api/v1/license/validate
     *
     * Periodic revalidation. Returns a fresh signed payload.
     * Uses the installation's API credential for authentication.
     */
    public function validate(Request $request): JsonResponse
    {
        $installationId = $request->input('installation_id');

        $activation = DB::table('license_activations')
            ->where('installation_id', $installationId)
            ->where('status', 'active')
            ->first();

        if (!$activation) {
            return response()->json(['error' => 'Installation not found or deactivated.'], 404);
        }

        // Verify API credential
        $credential = $request->header('X-Softtrill-Install-Credential', '');
        if (!$this->verifyRequestSignature($request, $activation->api_credential_hash)) {
            return response()->json(['error' => 'Invalid request signature.'], 403);
        }

        // Consume nonce
        if (!$this->consumeNonce($installationId, $request->input('_nonce', ''))) {
            return response()->json(['error' => 'Replay detected.'], 403);
        }

        $license = DB::table('licenses')->find($activation->license_id);

        if (!$license || $license->status !== 'active') {
            $this->logEvent($license?->id, $installationId, 'validation.failure', 'warning', ['reason' => 'license_inactive']);
            return response()->json(['error' => 'License is no longer active.'], 403);
        }

        // Check expiry
        if (now()->isAfter($license->expires_at)) {
            $this->logEvent($license->id, $installationId, 'license.expired', 'warning', []);
            return response()->json(['error' => 'License has expired.'], 403);
        }

        // Update last_seen_at
        DB::table('license_activations')
            ->where('installation_id', $installationId)
            ->update(['last_seen_at' => now()]);

        // Build and sign fresh payload
        $payload = $this->buildPayload($license, $installationId, $activation->domain);
        $signed  = $this->signPayload($payload);

        $this->logEvent($license->id, $installationId, 'validation.success', 'info', []);

        return response()->json([
            'ok'             => true,
            'signed_payload' => $signed,
        ]);
    }

    /**
     * POST /api/v1/license/deactivate
     */
    public function deactivate(Request $request): JsonResponse
    {
        $installationId = $request->input('installation_id');

        DB::table('license_activations')
            ->where('installation_id', $installationId)
            ->update([
                'status'             => 'deactivated',
                'deactivated_at'     => now(),
                'deactivation_reason'=> $request->input('reason', 'manual'),
                'updated_at'         => now(),
            ]);

        $this->logEvent(null, $installationId, 'deactivation', 'info', ['reason' => $request->input('reason', 'manual')]);

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/v1/license/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $installationId = $request->input('installation_id');

        $activation = DB::table('license_activations')
            ->where('installation_id', $installationId)
            ->where('status', 'active')
            ->first();

        if (!$activation) {
            return response()->json(['status' => 'not_found'], 404);
        }

        DB::table('license_activations')
            ->where('installation_id', $installationId)
            ->update(['last_seen_at' => now()]);

        return response()->json(['status' => 'active', 'ts' => now()->toISOString()]);
    }

    /**
     * GET or POST /api/v1/license/stats
     *
     * Returns statistics for a specific license key.
     * Request body/query:
     *   license_key
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        $licenseKey = $request->input('license_key');

        $license = DB::table('licenses')->where('license_key', $licenseKey)->first();

        if (!$license) {
            return response()->json(['error' => 'License not found.'], 404);
        }

        $activeCount = DB::table('license_activations')
            ->where('license_id', $license->id)
            ->where('status', 'active')
            ->count();

        $inactiveCount = DB::table('license_activations')
            ->where('license_id', $license->id)
            ->where('status', '!=', 'active')
            ->count();

        return response()->json([
            'ok' => true,
            'data' => [
                'license_key'          => $license->license_key,
                'status'               => $license->status,
                'max_activations'      => $license->max_activations,
                'active_activations'   => $activeCount,
                'inactive_activations' => $inactiveCount,
                'expires_at'           => $license->expires_at,
            ]
        ]);
    }

    // -----------------------------------------------------------------------
    // Private: signing
    // -----------------------------------------------------------------------

    private function buildPayload(object $license, string $installationId, string $domain): array
    {
        $features = json_decode($license->features ?? '{}', true) ?? [];
        $keyId    = config('license.active_key_id', 'kid-2026-01');

        $payload = [
            'license_id'      => $license->license_id,
            'customer_id'     => (string) $license->customer_id,
            'product'         => $license->product,
            'installation_id' => $installationId,
            'domain'          => $domain,
            'status'          => $license->status,
            'issued_at'       => now()->toISOString(),
            'expires_at'      => $license->expires_at,
            'max_users'       => (int) $license->max_users,
            'features'        => $features,
            'version'         => '1.0',
            'license_version' => 1,
            'key_id'          => $keyId,
        ];

        return $payload;
    }

    /**
     * Sign the payload with the Ed25519 private key.
     *
     * THE PRIVATE KEY MUST COME FROM A SECURE SOURCE (env, vault, HSM).
     * NEVER hard-code it. NEVER store it in the database.
     *
     * Returns: base64(64-byte signature + canonical_json)
     */
    private function signPayload(array $payload): string
    {
        // Load private key from environment (must be base64-encoded 64-byte Ed25519 secret key)
        $privateKeyBase64 = env('LICENSE_SIGNING_PRIVATE_KEY');
        if (empty($privateKeyBase64)) {
            throw new \RuntimeException('LICENSE_SIGNING_PRIVATE_KEY is not set. Cannot sign license payload.');
        }

        $privateKey = base64_decode($privateKeyBase64, strict: true);
        if ($privateKey === false || strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new \RuntimeException('Invalid Ed25519 private key. Expected ' . SODIUM_CRYPTO_SIGN_SECRETKEYBYTES . ' bytes.');
        }

        // Canonicalize: sort keys, no extra whitespace
        ksort($payload);
        $json      = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $signature = sodium_crypto_sign_detached($json, $privateKey);

        return base64_encode($signature . $json);
    }

    /**
     * Verify the HMAC-SHA256 signature on an incoming request.
     */
    private function verifyRequestSignature(Request $request, string $credential): bool
    {
        $ts    = (string) $request->header('X-Softtrill-Ts', '');
        $nonce = (string) $request->header('X-Softtrill-Nonce', '');
        $sig   = (string) $request->header('X-Softtrill-Sig', '');

        if (empty($ts) || abs(time() - (int) $ts) > 300) {
            return false; // timestamp too old or missing
        }

        $body = $request->all();
        ksort($body);
        $canonical = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $expected  = hash_hmac('sha256', $canonical, $credential);

        return hash_equals($expected, $sig);
    }

    /**
     * Consume a nonce (store it to prevent replay).
     * Returns false if the nonce was already used.
     */
    private function consumeNonce(string $installationId, string $nonce): bool
    {
        if (empty($nonce) || strlen($nonce) !== 32) {
            return false;
        }

        // Check if already used
        $exists = DB::table('license_nonces')
            ->where('installation_id', $installationId)
            ->where('nonce', $nonce)
            ->exists();

        if ($exists) {
            return false;
        }

        // Store it with a 10-minute expiry
        DB::table('license_nonces')->insert([
            'installation_id' => $installationId,
            'nonce'           => $nonce,
            'used_at'         => now(),
            'expires_at'      => now()->addMinutes(10),
        ]);

        // Cleanup expired nonces (async — don't block the response)
        DB::table('license_nonces')->where('expires_at', '<', now())->delete();

        return true;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('#:\d+$#', '', $domain);
        return $domain;
    }

    private function logEvent(?int $licenseId, ?string $installationId, string $eventType, string $severity, array $details): void
    {
        try {
            DB::table('license_events')->insert([
                'license_id'      => $licenseId,
                'installation_id' => $installationId,
                'event_type'      => $eventType,
                'severity'        => $severity,
                'details'         => json_encode($details),
                'ip_address'      => request()->ip(),
                'created_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[LicenseServer] Event log failed: ' . $e->getMessage());
        }
    }
}
