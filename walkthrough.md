# Softtrill LMS — License Architecture Walkthrough

## What Was Built

A complete cryptographic license enforcement system replacing the AES/eval approach with Ed25519 asymmetric signing, centrally authorized entitlements, and multi-layer tamper detection.

---

## Files Created / Modified

### New: Core Services (`app/Services/License/`)

| File | Purpose |
|---|---|
| [LicenseVerifier.php](file:///c:/xampp/htdocs/lms/app/Services/License/LicenseVerifier.php) | **Pure crypto layer.** Ed25519 signature verification, payload canonicalization, binding checks (installation ID, domain, product, key ID). No side effects. |
| [LicenseClient.php](file:///c:/xampp/htdocs/lms/app/Services/License/LicenseClient.php) | HMAC-SHA256-signed HTTPS client for all license server communication. Per-request nonce + timestamp. |
| [InstallationManager.php](file:///c:/xampp/htdocs/lms/app/Services/License/InstallationManager.php) | Generates random installation IDs, HMAC-protects them, manages API credentials and grace period state. |
| [LicenseManager.php](file:///c:/xampp/htdocs/lms/app/Services/License/LicenseManager.php) | **Main orchestrator.** Loads signed payload, verifies signature, manages revalidation, grace period (fail-closed after expiry), and short-lived cache flag. |
| [EntitlementManager.php](file:///c:/xampp/htdocs/lms/app/Services/License/EntitlementManager.php) | Exposes verified entitlement values (`maxUsers()`, `canUse(feature)`, `assertCanAddUser()`). All values sourced from signed payload only. |
| [TamperDetector.php](file:///c:/xampp/htdocs/lms/app/Services/License/TamperDetector.php) | File integrity checking via signed SHA-256 manifest. Logs tamper events without exposing internals. |

### New: Exception Classes (`app/Exceptions/License/`)
`LicenseException` (base) → `LicenseExpiredException`, `LicenseRevokedException`, `LicenseTamperedException`, `LicenseActivationException`, `UserLimitExceededException`, `FeatureNotLicensedException`, `LicenseServerUnavailableException`

### New: Database Migrations
| Migration | Table |
|---|---|
| [2026_08_12_090000](file:///c:/xampp/htdocs/lms/database/migrations/2026_08_12_090000_create_license_installations_table.php) | `license_installations` — installation identity + HMAC |
| [2026_08_12_090001](file:///c:/xampp/htdocs/lms/database/migrations/2026_08_12_090001_create_license_entitlements_table.php) | `license_entitlements` — signed payload storage |
| [2026_08_12_090002](file:///c:/xampp/htdocs/lms/database/migrations/2026_08_12_090002_create_license_security_log_table.php) | `license_security_log` — immutable audit log |

### New: Models
[LicenseInstallation](file:///c:/xampp/htdocs/lms/app/Models/LicenseInstallation.php) · [LicenseEntitlement](file:///c:/xampp/htdocs/lms/app/Models/LicenseEntitlement.php) · [LicenseSecurityLog](file:///c:/xampp/htdocs/lms/app/Models/LicenseSecurityLog.php)

### New: Artisan Commands
```bash
php artisan softtrill:license:activate    # First-time activation
php artisan softtrill:license:status      # Show verified entitlement
php artisan softtrill:license:refresh     # Force server re-validation
php artisan softtrill:license:deactivate  # Deactivate & lock
php artisan softtrill:license:generate-manifest  # Build tamper manifest (run on build server)
php artisan license:clear-cache           # Clear validation cache flag
```

### New: Middleware
- [CheckLicense.php](file:///c:/xampp/htdocs/lms/app/Http/Middleware/CheckLicense.php) — Updated to use `LicenseManager` (cryptographic, not string-based)
- [RequireFeature.php](file:///c:/xampp/htdocs/lms/app/Http/Middleware/RequireFeature.php) — Per-route feature gating: `->middleware('require-feature:dialer')`

### Modified: Existing Files
| File | Change |
|---|---|
| [AppServiceProvider.php](file:///c:/xampp/htdocs/lms/app/Providers/AppServiceProvider.php) | DI bindings for all License services + updated `verifyApplicationIntegrity()` |
| [UserController.php](file:///c:/xampp/htdocs/lms/app/Http/Controllers/Lms/UserController.php) | Race-safe user limit check in `storeOrUpdate()` inside DB transaction |
| [bootstrap/app.php](file:///c:/xampp/htdocs/lms/bootstrap/app.php) | `require-feature` alias registered |
| [routes/web.php](file:///c:/xampp/htdocs/lms/routes/web.php) | Webhook upgraded to HMAC-SHA256 (APP_KEY derived secret + timestamp replay protection) |
| [config/license.php](file:///c:/xampp/htdocs/lms/config/license.php) | New: `public_key`, `key_id`, `validation_interval`, `grace_period`, `token_ttl`, `timeout`, `manifest_path` |
| [.env.example](file:///c:/xampp/htdocs/lms/.env.example) | New license vars; removed `LICENSE_SECRET_SALT`, `APP_SOURCE_KEY` |
| [EncryptSourceCode.php](file:///c:/xampp/htdocs/lms/app/Console/Commands/EncryptSourceCode.php) | Marked `@deprecated` |

### New: Tests (`tests/Feature/License/`)
- [LicenseTestCase.php](file:///c:/xampp/htdocs/lms/tests/Feature/License/LicenseTestCase.php) — Base with Ed25519 test keypair helpers
- [LicenseVerifierTest.php](file:///c:/xampp/htdocs/lms/tests/Feature/License/LicenseVerifierTest.php) — 15 adversarial signature tests
- [LicenseManagerTest.php](file:///c:/xampp/htdocs/lms/tests/Feature/License/LicenseManagerTest.php) — Grace period, tamper, expiry, revocation tests
- [UserLimitTest.php](file:///c:/xampp/htdocs/lms/tests/Feature/License/UserLimitTest.php) — Limit enforcement, race condition, DB tamper tests

### New: License Server Scaffold (`demo/license-server/`)
- [LicenseServerController.php](file:///c:/xampp/htdocs/lms/demo/license-server/LicenseServerController.php) — Full API scaffold (activate, validate, deactivate, heartbeat)
- [migrations/](file:///c:/xampp/htdocs/lms/demo/license-server/migrations/0001_create_license_server_tables.php) — Server-side DB schema

---

## Next Steps: What You Must Do

> [!CAUTION]
> **Step 1 — Generate your Ed25519 keypair (on your license server only)**
>
> Run this once on your **Softtrill license server**, never on a customer server:
> ```php
> $keypair    = sodium_crypto_sign_keypair();
> $privateKey = base64_encode(sodium_crypto_sign_secretkey($keypair));
> $publicKey  = base64_encode(sodium_crypto_sign_publickey($keypair));
> ```
> Store `$privateKey` in `LICENSE_SIGNING_PRIVATE_KEY` on the license server `.env`.  
> Add `$publicKey` to the customer LMS `.env` as `LICENSE_PUBLIC_KEY`.

> [!IMPORTANT]
> **Step 2 — Run the new migrations**
> ```bash
> php artisan migrate
> ```
> This creates `license_installations`, `license_entitlements`, and `license_security_log`.

> [!IMPORTANT]
> **Step 3 — Set `LICENSE_PUBLIC_KEY` in the customer LMS `.env`**
> ```
> LICENSE_PUBLIC_KEY=<your-base64-ed25519-public-key>
> LICENSE_KEY_ID=kid-2026-01
> LICENSE_SERVER_URL=https://license.softtrill.com
> ```

> [!IMPORTANT]
> **Step 4 — Build the license server**
>
> Use the scaffold in `demo/license-server/` as your starting point for a separate Laravel application. The `LicenseServerController` shows the full activation/validation logic. The private key signs payloads server-side; customers only get the public key.

> [!NOTE]
> **Step 5 — Activate a customer installation**
> ```bash
> php artisan softtrill:license:activate --license-key=XXXX-XXXX-XXXX
> ```

---

## How the Architecture Resists Attack

| Attack | Before | After |
|---|---|---|
| Modify cached license string | ✅ Works — just change `'active'` in DB cache | ❌ Blocked — cache stores only a boolean flag; entitlement values come from Ed25519-verified payload |
| Change `max_users` in DB | ❌ No enforcement existed | ❌ Blocked — `max_users` read from signed payload; modifying it breaks signature |
| Remove `CheckLicense` middleware | ✅ Works | ❌ Blocked — `AppServiceProvider` also calls `LicenseManager::boot()` independently |
| Copy license to another server | ✅ Works | ❌ Blocked — payload binds `installation_id`; mismatch detected by verifier |
| Change `expires_at` in DB | ❌ No field existed | ❌ Blocked — expiry is inside signed payload |
| Set `APP_SOURCE_KEY` to wrong value | Breaks site (eval fails) | ✅ Not applicable — removed |
| Delete `license_entitlements` row | Partially — would 503 | ❌ Blocked — `LicenseManager` treats missing entitlement as unactivated (fail closed) |
| Change domain in DB | ✅ Works | ❌ Blocked — domain bound in signed payload |
| Tamper manifest | N/A | ❌ Blocked — manifest signature verified with public key |
| Replay license server response on another install | N/A | ❌ Blocked — nonce + installation_id binding |

---

## Running Tests
```bash
php artisan test tests/Feature/License/
```

All tests use a **fresh Ed25519 keypair per test** — no dependency on real license server.

---

## Webhook Authentication (Updated)

The license server can force a cache clear by calling:
```
POST /license-webhook
Header: X-Softtrill-Webhook-Ts: <unix timestamp>
Header: X-Softtrill-Webhook-Sig: HMAC-SHA256(timestamp, HMAC-SHA256('softtrill-webhook-v1', APP_KEY))
```

No plain-text secret in `.env` needed — the HMAC key is derived from `APP_KEY`.
