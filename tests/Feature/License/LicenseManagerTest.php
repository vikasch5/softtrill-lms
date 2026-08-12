<?php

namespace Tests\Feature\License;

use App\Exceptions\License\LicenseExpiredException;
use App\Exceptions\License\LicenseRevokedException;
use App\Exceptions\License\LicenseServerUnavailableException;
use App\Exceptions\License\LicenseTamperedException;
use App\Models\LicenseEntitlement;
use App\Models\LicenseInstallation;
use App\Services\License\LicenseClient;
use App\Services\License\LicenseManager;
use Mockery;

/**
 * Tests for LicenseManager lifecycle and grace period behavior.
 *
 * Adversarial scenarios:
 * - Expired license payload fails closed
 * - Revoked license fails closed
 * - Server unreachable within grace period → allowed
 * - Server unreachable after grace period → fail closed
 * - Tampered local entitlement detected
 * - Missing entitlement fails closed
 */
class LicenseManagerTest extends LicenseTestCase
{
    // -----------------------------------------------------------------------
    // Valid license
    // -----------------------------------------------------------------------

    public function test_valid_active_license_passes(): void
    {
        $this->createActiveInstallation();

        $manager = app(LicenseManager::class);
        $payload  = $manager->validateOrFail();

        $this->assertEquals('active', $payload['status']);
        $this->assertEquals($this->testInstallationId, $payload['installation_id']);
    }

    // -----------------------------------------------------------------------
    // Expired license
    // -----------------------------------------------------------------------

    public function test_expired_license_throws_exception(): void
    {
        $this->expectException(LicenseExpiredException::class);

        $this->createActiveInstallation([
            'expires_at' => now()->subDay()->toISOString(),
        ]);

        app(LicenseManager::class)->clearCache();
        app(LicenseManager::class)->validateOrFail();
    }

    // -----------------------------------------------------------------------
    // Revoked license
    // -----------------------------------------------------------------------

    public function test_revoked_license_throws_exception(): void
    {
        $this->expectException(LicenseRevokedException::class);

        $this->createActiveInstallation(['status' => 'revoked']);

        app(LicenseManager::class)->clearCache();
        app(LicenseManager::class)->validateOrFail();
    }

    public function test_suspended_license_throws_exception(): void
    {
        $this->expectException(LicenseRevokedException::class);

        $this->createActiveInstallation(['status' => 'suspended']);

        app(LicenseManager::class)->clearCache();
        app(LicenseManager::class)->validateOrFail();
    }

    // -----------------------------------------------------------------------
    // Missing entitlement
    // -----------------------------------------------------------------------

    public function test_no_entitlement_throws_exception(): void
    {
        $this->expectException(LicenseTamperedException::class);

        // Create an active installation but no entitlement
        $appKey = config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }
        $derivedKey = hash_hmac('sha256', 'softtrill-installation-v1', $appKey, true);
        $hmac       = hash_hmac('sha256', $this->testInstallationId, $derivedKey);

        LicenseInstallation::create([
            'installation_id'     => $this->testInstallationId,
            'installation_hmac'   => $hmac,
            'api_credential'      => bin2hex(random_bytes(32)),
            'api_credential_hash' => bcrypt('test'),
            'domain'              => $this->testDomain,
            'status'              => 'active',
            'activated_at'        => now(),
        ]);

        // Mock client to also fail (simulate server down)
        $this->mock(LicenseClient::class, fn($m) => $m->shouldReceive('validate')->andThrow(new \RuntimeException('unreachable')));

        app(LicenseManager::class)->clearCache();
        app(LicenseManager::class)->validateOrFail();
    }

    // -----------------------------------------------------------------------
    // Grace period
    // -----------------------------------------------------------------------

    public function test_within_grace_period_allows_access_when_server_down(): void
    {
        $this->createActiveInstallation();

        // Set up so the entitlement needs revalidation (cached_until in past)
        LicenseEntitlement::first()->update(['cached_until' => now()->subMinutes(5)]);

        // Set an active grace period
        LicenseInstallation::first()->update([
            'grace_expires_at' => now()->addHours(48),
        ]);

        // Mock the client to fail
        $this->mock(LicenseClient::class, function ($mock) {
            $mock->shouldReceive('validate')->andThrow(new \RuntimeException('Server down'));
        });

        app(LicenseManager::class)->clearCache();
        // Should NOT throw — grace period is active
        $payload = app(LicenseManager::class)->validateOrFail();
        $this->assertEquals('active', $payload['status']);
    }

    public function test_expired_grace_period_fails_closed(): void
    {
        $this->expectException(LicenseServerUnavailableException::class);

        $this->createActiveInstallation();

        // Entitlement needs revalidation
        LicenseEntitlement::first()->update(['cached_until' => now()->subMinutes(5)]);

        // Grace period already expired
        LicenseInstallation::first()->update([
            'grace_expires_at' => now()->subMinutes(1),
        ]);

        // Mock the client to fail
        $this->mock(LicenseClient::class, function ($mock) {
            $mock->shouldReceive('validate')->andThrow(new \RuntimeException('Server down'));
        });

        app(LicenseManager::class)->clearCache();
        app(LicenseManager::class)->validateOrFail();
    }

    // -----------------------------------------------------------------------
    // Tampered entitlement
    // -----------------------------------------------------------------------

    public function test_tampered_signed_payload_in_db_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $this->createActiveInstallation();

        // Directly tamper with the payload in DB
        $record = LicenseEntitlement::first();
        $raw    = base64_decode($record->signed_payload);
        $sig    = substr($raw, 0, 64);
        $json   = substr($raw, 64);
        // Change max_users without re-signing
        $tampered = str_replace('"max_users":100', '"max_users":9999', $json);
        $record->update(['signed_payload' => base64_encode($sig . $tampered)]);

        app(LicenseManager::class)->clearCache();
        app(LicenseManager::class)->validateOrFail();
    }

    // -----------------------------------------------------------------------
    // DB-level installation ID tampering
    // -----------------------------------------------------------------------

    public function test_tampered_installation_id_in_db_fails(): void
    {
        $this->expectException(LicenseTamperedException::class);

        $this->createActiveInstallation();

        // Change installation_id without updating the HMAC
        LicenseInstallation::first()->update(['installation_id' => bin2hex(random_bytes(32))]);

        app(LicenseManager::class)->clearCache();
        app(LicenseManager::class)->validateOrFail();
    }

    // -----------------------------------------------------------------------
    // Cache clearing
    // -----------------------------------------------------------------------

    public function test_clear_cache_forces_re_verification(): void
    {
        $this->createActiveInstallation();

        $manager = app(LicenseManager::class);
        $manager->validateOrFail(); // prime cache

        $manager->clearCache();

        // After clearing, should re-verify without error
        $payload = $manager->validateOrFail();
        $this->assertEquals('active', $payload['status']);
    }
}
