<?php

return [

    /*
    |--------------------------------------------------------------------------
    | License Server URL
    |--------------------------------------------------------------------------
    | The base URL of your Softtrill central license server.
    | All API calls will be sent to this endpoint.
    */
    'server_url' => env('LICENSE_SERVER_URL', 'https://license.softtrill.com'),

    /*
    |--------------------------------------------------------------------------
    | Ed25519 Public Key
    |--------------------------------------------------------------------------
    | The Ed25519 public key used to verify signed license payloads.
    | This is BASE64-encoded. The corresponding PRIVATE KEY must NEVER
    | leave the Softtrill license server. This public key is safe to distribute.
    |
    | Generate a keypair on your license server with:
    |   $keypair = sodium_crypto_sign_keypair();
    |   $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
    |   $publicKey  = base64_encode(sodium_crypto_sign_publickey($keypair));
    |
    | Set LICENSE_PUBLIC_KEY in .env to enable key rotation without code changes.
    | The fallback below is a placeholder — replace it with your real public key.
    */
    'public_key' => env(
        'LICENSE_PUBLIC_KEY',
        // REPLACE THIS WITH YOUR REAL Ed25519 PUBLIC KEY (base64-encoded, 32 bytes → 44 chars)
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' // placeholder — set LICENSE_PUBLIC_KEY in .env
    ),

    /*
    |--------------------------------------------------------------------------
    | Key ID
    |--------------------------------------------------------------------------
    | Identifies which keypair signed this payload. Allows key rotation.
    | Must match the key_id in the signed license payload.
    */
    'key_id' => env('LICENSE_KEY_ID', 'kid-2026-01'),

    /*
    |--------------------------------------------------------------------------
    | Product Identifier
    |--------------------------------------------------------------------------
    | Must match the "product" field in the signed license payload.
    */
    'product' => 'softtrill-lms',

    /*
    |--------------------------------------------------------------------------
    | Validation Interval (seconds)
    |--------------------------------------------------------------------------
    | How often the LMS contacts the license server to obtain a fresh signed
    | payload. The local signed payload is considered authoritative until this
    | interval expires. Default: 24 hours.
    */
    'validation_interval' => (int) env('LICENSE_VALIDATION_INTERVAL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Grace Period (seconds)
    |--------------------------------------------------------------------------
    | If the license server is unreachable AND the cached signed payload is
    | still cryptographically valid (signature passes, not expired), the
    | application will continue to operate for this many seconds since the
    | last successful validation. After this period: FAIL CLOSED.
    | Default: 72 hours.
    */
    'grace_period' => (int) env('LICENSE_GRACE_PERIOD', 259200),

    /*
    |--------------------------------------------------------------------------
    | Short-Lived Token TTL (seconds)
    |--------------------------------------------------------------------------
    | Expiry for single-use operation authorization tokens issued by the
    | license server for high-security operations.
    | Default: 5 minutes.
    */
    'token_ttl' => (int) env('LICENSE_TOKEN_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    | Maximum time to wait for the license server to respond.
    | Keep this low to avoid blocking page loads.
    */
    'timeout' => (int) env('LICENSE_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Tamper Detection
    |--------------------------------------------------------------------------
    | Path to the signed manifest file used for integrity checking.
    | Set to null to disable tamper detection.
    */
    'manifest_path' => base_path('softtrill.manifest.json'),

];
