# Softtrill LMS — High-Security License & Anti-Tamper Architecture

## Background

After thorough inspection of the existing LMS codebase, here is what was found and what this plan addresses.

---

## Existing Implementation Audit

### What Currently Exists

| Component | Current State | Vulnerability |
|---|---|---|
| `LicenseService::check()` | Calls `/api/license/verify`, caches plain string `'active'` | Cache stores untrusted string; customer can poison cache driver |
| `CheckLicense` middleware | `if status !== 'active' → abort` | Customer can comment out middleware registration in `bootstrap/app.php` |
| `AppServiceProvider::verifyApplicationIntegrity()` | Also calls `LicenseService::check()` | Same vulnerability; customer can remove the call |
| `config/license.php` | Stores `server_url` and `secret_salt` in `.env` | Secret salt shared across installations; same key for all |
| `EncryptSourceCode` / `DecryptSourceCode` | AES-256-CBC + eval() stubs | Explicitly to be removed per request |
| `LicenseService::buildFingerprint()` | `hash(installation_id + domain + MAC + salt)` | MAC address is spoofable; salt is in `.env` |
| Installation ID | Stored as plain text in `settings` table | Customer can change DB row |
| License key | Stored as plain text in `settings` table | Customer can copy to another server |
| User limit | **No enforcement exists at all** | Any number of users can be created |
| Domain binding | Sent to server but no local cryptographic check | Server response not signed |
| Grace period | Fail-open: `'unreachable'` → abort(503) | But grace cache is plain string |
| Webhook | `secret_salt` checked in plain `.env` | Single shared secret |

### Critical Gaps Identified

1. **No cryptographic signature** on any license response — the LMS trusts whatever string comes back from the server.
2. **No user limit enforcement** — `UserController::storeOrUpdate()` creates users without any license limit check.
3. **Installation ID stored as plain text** — changing the DB row changes the installation identity.
4. **License cache is a plain string** — a customer can manipulate the cache driver.
5. **`bootstrap/app.php` middleware registration** — trivially removable by customer.
6. **No tamper detection** of critical files.
7. **No signed entitlement** — expiry, max_users, features never cryptographically bound to installation.
8. **Same `secret_salt` pattern** — if leaked, usable across installations.

---

## Open Questions

> [!IMPORTANT]
> **Q1: Do you have a separate "Softtrill License Server" already running, or does this plan need to also scaffold a license server application?**
>
> This plan covers: (A) the **LMS client** side fully, and (B) the **license server API contract** (migrations + endpoint specs). The actual license server Laravel app can be built as a separate project. Please confirm if you need the full license server app code too.

> [!IMPORTANT]
> **Q2: Ed25519 availability** — PHP 8.2+ with `libsodium` (bundled by default) supports Ed25519 via `sodium_crypto_sign_*`. XAMPP on Windows may or may not have it enabled. Do you want me to include an automatic fallback to RSA-3072 if `sodium` is unavailable, or can we confirm `sodium` is enabled first?

> [!IMPORTANT]
> **Q3: Offline grace period** — The current code has a 24-hour fail-open grace. The requirement says "fail-closed after grace expires." For the **initial implementation**, what is the acceptable grace period? (Recommendation: 72 hours for normal license, 24 hours for high-security).

> [!CAUTION]
> **Q4: Existing settings table** — The `settings` table stores `installation_id` and `license_key` as plain text. The new architecture will store the signed license blob in the `settings` table. **Existing `installation_id` rows will be invalidated** during the migration since we are switching to a new keyed, HMAC-protected installation identity system. This means existing customer installations will need to re-activate. **Is this acceptable?**

> [!WARNING]
> **Q5: AES/eval removal** — The `EncryptSourceCode`, `DecryptSourceCode`, and `ClearLicenseCache` commands will be **deprecated** (not deleted, in case you need them for transition). The `APP_SOURCE_KEY` env variable will no longer be used. Confirm this is acceptable.

---

## Security Threat Model

### Attacker A — Normal Application User
- **Can modify**: Nothing server-side; only their own browser
- **Can read**: Only what the UI exposes
- **Can bypass**: Nothing in the new architecture
- **Remains protected**: All license and entitlement decisions

### Attacker B — Customer Developer (Laravel Source Access)
- **Can modify**: Any PHP file, routes, middleware, controllers, services
- **Can read**: Public key (intentional), all PHP logic, `.env` (if server access)
- **Can bypass locally**: Middleware checks, boolean returns from `LicenseEntitlementManager`, local cache
- **Cannot bypass**: Cryptographic signature verification of signed license payload (Ed25519); the public key is embedded in compiled config — modifying it doesn't help unless they also have the private key; server-authorized operations (activation, user limit grants) require a fresh signed token from the license server
- **Limitation (honest)**: If a developer completely rewrites the LMS business logic, they can remove all license checks. However, they **cannot forge a valid signed license payload** without the private key. Server-side operations requiring fresh authorization cannot be bypassed locally.

### Attacker C — Customer with Database Access
- **Can modify**: Any database row including `settings`, `users`, `license_*` tables
- **Can bypass locally**: Changing `settings.value` for `installation_id` — but signed entitlement verifies installation ID is bound
- **Cannot bypass**: Signed entitlement payload — changing `max_users` in DB does not affect the cryptographically verified entitlement; the signature fails
- **Remains protected**: Entitlement values (max_users, expires_at, features, domain, installation_id) are all inside the signed payload

### Attacker D — Customer with Filesystem Access
- **Can modify**: PHP files, `.env`, `storage/` cached license file
- **Can bypass locally**: Modify cached license file — but HMAC/Ed25519 verification catches it
- **Cannot bypass**: The embedded public key (it's compiled into config; changing it doesn't provide access because they don't have the corresponding private key); signed license responses
- **Remains protected**: Private key never on filesystem; signed entitlement

### Attacker E — Customer with Root / Server Admin Access
- **Can modify**: Everything — kernel, PHP binary, DNS, firewall
- **Bypass potential**: At root level, a sufficiently motivated attacker can patch `php.ini`, intercept TLS (if they control DNS), or patch the `sodium` extension
- **Honest limitation**: No software-only license system can fully protect against a determined root-level attacker. The goal is to raise the attack cost above the value of the license.
- **Architecture mitigation**: Signed payloads, short-lived authorization tokens, periodic server revalidation, installation binding, audit logging on the license server side

---

## Proposed Architecture

### Core Cryptographic Model

```
SOFTTRILL LICENSE SERVER
  ├── Ed25519 PRIVATE KEY (never leaves)
  ├── Issues signed license payloads
  ├── Issues short-lived operation tokens
  └── Maintains activation/revocation database

CUSTOMER LMS
  ├── Ed25519 PUBLIC KEY (embedded in config, read-only)
  ├── Signed license cache (verified on every load)
  ├── Installation identity (HMAC-protected)
  └── Local enforcement (secondary layer)
```

### Signed License Payload (canonical JSON, Ed25519-signed)

```json
{
  "license_id": "uuid",
  "customer_id": "uuid",
  "product": "softtrill-lms",
  "installation_id": "uuid",
  "domain": "example.com",
  "status": "active",
  "issued_at": "2026-08-12T00:00:00Z",
  "expires_at": "2027-08-12T00:00:00Z",
  "max_users": 100,
  "features": {"dialer": true, "export": true},
  "version": "1.0",
  "license_version": 1,
  "key_id": "kid-2026-01"
}
```

The payload is JSON-encoded with sorted keys (canonical), then signed with Ed25519. The signature + payload are stored together as the signed entitlement.

---

## Proposed Changes

### License Server Side (API Contract — separate app or new Laravel project)

#### [NEW] License Server Database Schema

Tables to create on the license server:
- `licenses` — master license records
- `license_activations` — per-installation activation records
- `license_events` — audit log
- `license_api_keys` — per-installation API credentials
- `license_features` — feature flags per license
- `license_nonces` — replay prevention

#### [NEW] License Server Endpoints

```
POST /api/v1/license/activate    — validates license key, installation, domain; returns signed payload
POST /api/v1/license/validate    — heartbeat; returns fresh signed payload
POST /api/v1/license/deactivate  — deactivates installation
POST /api/v1/license/heartbeat   — lightweight check; returns short-lived signed token
POST /api/v1/license/authorize   — issues short-lived signed authorization for critical operations
```

---

### LMS Client Side (this repository)

#### [NEW] `config/license.php` — Expanded
- License server URL
- Embedded Ed25519 public key (base64)
- Key ID
- Cache TTL
- Grace period TTL
- Validation interval

---

#### [NEW] `app/Services/License/` Directory

##### [NEW] `LicenseClient.php`
Handles all HTTPS communication with the license server:
- Authenticated requests (per-installation HMAC-signed)
- Nonce generation
- Timestamp binding
- TLS enforcement
- Timeout handling

##### [NEW] `LicenseVerifier.php`
Pure cryptographic verification:
- Ed25519 signature verification using `sodium_crypto_sign_open()`
- Payload canonicalization
- Key ID validation
- Expiry check
- Installation ID binding check
- Domain check
- Product check
- Status check

##### [NEW] `LicenseManager.php`
Orchestrates the full license lifecycle:
- Loads signed entitlement from secure local cache
- Verifies signature on every load
- Triggers re-validation when cache expired
- Manages grace period
- Fail-closed after grace expires
- Coordinates with `LicenseClient` for server calls

##### [NEW] `EntitlementManager.php`
Provides the application with license entitlements from the verified payload:
- `isValid(): bool`
- `canUse(string $feature): bool`
- `maxUsers(): int`
- `expiresAt(): Carbon`
- `installationId(): string`
- `currentUserCount(): int`
- `canAddUser(): bool` — uses DB lock + signed entitlement
- All values sourced from cryptographically verified payload only

##### [NEW] `InstallationManager.php`
Manages the installation identity:
- Generates installation ID on first boot (cryptographically random, 32 bytes)
- Stores installation ID as HMAC-protected record (not plain text)
- Detects if installation ID has been tampered
- Generates installation API credential (unique per installation)

##### [NEW] `TamperDetector.php`
File integrity monitoring:
- Verifies SHA-256 hashes of critical files against a signed manifest
- Manifest is signed by Softtrill private key (verified with public key)
- If tamper detected: logs event, invalidates local cache, triggers re-validation
- Reports tamper events to license server

---

#### [MODIFY] `app/Http/Middleware/CheckLicense.php`
- Uses `LicenseManager` instead of `LicenseService`
- Verifies cryptographic entitlement, not a plain string
- Cannot be bypassed by simply commenting out (the service provider also checks)

#### [NEW] `app/Http/Middleware/RequireFeature.php`
- Parameterized: `->middleware('require-feature:dialer')`
- Checks signed entitlement for feature flag

#### [NEW] `app/Http/Middleware/CheckUserLimit.php`
- Applied to user creation route
- Checks signed entitlement `max_users`
- Uses DB transaction + locking to prevent race condition

---

#### [MODIFY] `app/Http/Controllers/Lms/UserController.php`
- `storeOrUpdate()`: wraps user creation in `EntitlementManager::canAddUser()` check
- The check uses a DB-level lock to prevent concurrent bypass

---

#### [NEW] `app/Console/Commands/License/LicenseActivate.php`
```
php artisan softtrill:license:activate {--license-key=} {--domain=}
```

#### [NEW] `app/Console/Commands/License/LicenseStatus.php`
```
php artisan softtrill:license:status
```

#### [NEW] `app/Console/Commands/License/LicenseRefresh.php`
```
php artisan softtrill:license:refresh
```

#### [NEW] `app/Console/Commands/License/LicenseDeactivate.php`
```
php artisan softtrill:license:deactivate
```

#### [MODIFY] `app/Console/Commands/ClearLicenseCache.php`
- Updated to use new `LicenseManager::clearCache()`

#### [DEPRECATED] `app/Console/Commands/EncryptSourceCode.php`
- Remains in codebase but is not removed (in case needed for rollback)
- Annotated as deprecated

#### [DEPRECATED] `app/Console/Commands/DecryptSourceCode.php`
- Same as above

---

#### [NEW] Database Migrations (LMS side)

##### `create_license_installations_table.php`
```
license_installations:
  id (bigint, PK)
  installation_id (char(64), unique) — HMAC-protected UUID
  installation_hmac (char(64)) — integrity check of installation_id
  api_credential (char(64), unique) — per-installation API token
  api_credential_hash (char(64)) — bcrypt hash for comparison
  domain (varchar 255)
  activated_at (timestamp, nullable)
  last_validated_at (timestamp, nullable)
  grace_expires_at (timestamp, nullable)
  created_at, updated_at
```

##### `create_license_entitlements_table.php`
```
license_entitlements:
  id (bigint, PK)
  installation_id (char(64), FK → license_installations)
  signed_payload (longtext) — base64(signature + payload)
  payload_hash (char(64)) — SHA-256 of payload for quick lookup
  issued_at (timestamp)
  expires_at (timestamp)
  cached_until (timestamp) — when to next re-validate
  status (enum: active, expired, revoked, suspended)
  created_at, updated_at
```

##### `create_license_security_log_table.php`
```
license_security_log:
  id (bigint, PK)
  installation_id (char(64), nullable)
  event_type (varchar 100) — activation, validation, tamper, replay, failure...
  severity (enum: info, warning, critical)
  details (json, nullable)
  ip_address (varchar 45, nullable)
  created_at
```

---

#### [MODIFY] `app/Providers/AppServiceProvider.php`
- Replace `LicenseService::check()` with `LicenseManager::boot()`
- `boot()` loads signed entitlement, verifies signature, checks expiry
- If entitlement invalid/expired → triggers background re-validation or fails closed

---

#### [MODIFY] `config/license.php`
```php
return [
    'server_url'          => env('LICENSE_SERVER_URL', 'https://license.softtrill.com'),
    'public_key'          => env('LICENSE_PUBLIC_KEY', '<base64-ed25519-public-key>'),
    'key_id'              => env('LICENSE_KEY_ID', 'kid-2026-01'),
    'product'             => 'softtrill-lms',
    'cache_ttl'           => env('LICENSE_CACHE_TTL', 3600),        // 1 hour
    'validation_interval' => env('LICENSE_VALIDATION_INTERVAL', 86400), // 24 hours
    'grace_period'        => env('LICENSE_GRACE_PERIOD', 259200),   // 72 hours
    'token_ttl'           => env('LICENSE_TOKEN_TTL', 300),         // 5 min short-lived tokens
    'timeout'             => env('LICENSE_TIMEOUT', 10),            // HTTP timeout
];
```

> [!WARNING]
> The `LICENSE_PUBLIC_KEY` can be embedded directly in `config/license.php` as a fallback default (it is a public key — safe to distribute). The `.env` variable allows key rotation without code changes.

---

#### [NEW] `app/Exceptions/LicenseException.php`
- `LicenseExpiredException`
- `LicenseRevokedException`
- `LicenseTamperedException`
- `LicenseActivationException`
- `UserLimitExceededException`
- `FeatureNotLicensedException`

---

#### [NEW] `tests/Feature/License/`

Test files covering:
- `ValidLicenseTest.php`
- `ExpiredLicenseTest.php`
- `RevokedLicenseTest.php`
- `InvalidSignatureTest.php`
- `ModifiedPayloadTest.php`
- `TamperedCacheTest.php`
- `InstallationMismatchTest.php`
- `DomainMismatchTest.php`
- `UserLimitTest.php`
- `ConcurrentUserCreationTest.php`
- `GracePeriodTest.php`
- `OfflineModeTest.php`
- `ReplayAttackTest.php`
- `TamperDetectionTest.php`

---

#### [NEW] Signed Manifest for Tamper Detection

A `softtrill.manifest.json` file will be distributed with each release:
```json
{
  "version": "1.0.0",
  "release_id": "uuid",
  "issued_at": "...",
  "files": {
    "app/Services/License/LicenseVerifier.php": "sha256-hash",
    "app/Services/License/LicenseManager.php": "sha256-hash",
    "app/Http/Middleware/CheckLicense.php": "sha256-hash",
    ...
  },
  "signature": "base64-ed25519-signature"
}
```

The `TamperDetector` verifies the manifest signature and then checks file hashes.

---

## Key Security Design Decisions

### Why Ed25519?
- Deterministic, fast, resistant to side-channel attacks
- Small key/signature sizes (32-byte key, 64-byte signature)
- Available natively in PHP 8.x via `libsodium` (bundled with PHP 8.0+)
- No padding oracle issues (unlike RSA)

### Why signed payload vs. API call on every request?
- Performance: API call on every request would be too slow
- The **signed payload acts as a short-lived certificate**
- The signature is verified locally using the public key on every entitlement check
- The cached signed payload expires (configurable), forcing re-validation
- Critical operations (user creation) can optionally force a fresh API call

### Why not trust the cache driver?
- Laravel's database/file cache can be manipulated by the customer
- The new architecture stores the **signed payload** in cache, not a boolean or string
- Even if the customer manipulates the cache, the `LicenseVerifier` will reject any payload with an invalid Ed25519 signature
- Modifying `max_users: 100` to `max_users: 9999` in the cached payload will cause signature verification to fail

### Why HMAC-protect the installation ID?
- The installation ID is stored in the `settings` table
- Adding an HMAC (keyed hash) allows the application to detect if the row was modified directly in the database
- The HMAC key is derived from a combination of the `APP_KEY` and a server-side salt
- However, since the customer controls `APP_KEY`, this is a secondary protection — the primary protection is that the signed license payload contains the expected installation ID, and the server won't issue a valid signed payload for a tampered installation ID

### Why per-installation API credentials?
- Single global API key would allow any customer to impersonate another
- Each installation gets a unique API credential during activation
- Credentials can be revoked server-side independently
- API requests are HMAC-signed with timestamp + nonce to prevent replay

---

## Activation Flow

```
1. Admin runs: php artisan softtrill:license:activate --license-key=XXXX
2. InstallationManager generates installation_id (32 random bytes → hex)
3. InstallationManager generates installation API credential
4. LicenseClient sends to license server:
   {
     license_key, installation_id, domain,
     product, request_timestamp, nonce,
     hmac_signature (HMAC of above fields with license_key)
   }
5. License server:
   a. Validates license_key exists and is active
   b. Checks activation limit not exceeded
   c. Checks domain policy
   d. Records activation in license_activations table
   e. Returns signed license payload (Ed25519 signed)
6. LMS:
   a. Verifies signature with public key
   b. Verifies installation_id matches
   c. Verifies domain matches
   d. Stores signed payload in license_entitlements table
   e. Logs activation event
```

## Validation Flow (periodic)

```
1. LicenseManager::getEntitlement() called
2. Load signed payload from license_entitlements table
3. Verify Ed25519 signature → fail-closed if invalid
4. Check cached_until timestamp:
   a. If not expired → return entitlement from payload
   b. If expired → call license server for fresh payload
5. License server returns fresh signed payload
6. LMS verifies signature, stores new payload
7. If server unreachable:
   a. Check grace_expires_at
   b. If within grace period → use existing (still valid signature) payload
   c. If grace expired → fail-closed
```

## User Creation Flow (with limit enforcement)

```
1. UserController::storeOrUpdate() called
2. DB::transaction() begins with FOR UPDATE lock on entitlement record
3. EntitlementManager::canAddUser() called inside transaction:
   a. Load signed entitlement → verify signature
   b. Get max_users from verified payload
   c. Count current active users (within same transaction)
   d. If count >= max_users → throw UserLimitExceededException
   e. Else → proceed with user creation
4. User created inside transaction
5. Transaction committed
```

This prevents two simultaneous requests both seeing count=99 when max=100 and both succeeding.

---

## Verification Plan

### Automated Tests
```bash
php artisan test tests/Feature/License/
```

### Manual Verification Steps
1. Run activation command, verify signed payload stored
2. Modify `max_users` in stored payload → verify next request rejects signature
3. Modify `expires_at` in stored payload → verify rejection
4. Change `installation_id` in DB → verify mismatch detected
5. Try to create users beyond license limit → verify locked
6. Simulate concurrent user creation at limit boundary → verify race safety
7. Shut down license server → verify grace period behavior
8. Wait for grace period to expire → verify fail-closed
9. Restore license server → verify re-validation and recovery
10. Run tamper detection with modified critical file → verify detection
