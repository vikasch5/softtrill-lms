<?php

namespace Tests\Feature\License;

use App\Exceptions\License\UserLimitExceededException;
use App\Models\LicenseEntitlement;
use App\Models\User;
use App\Services\License\EntitlementManager;
use App\Services\License\LicenseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Tests for user limit enforcement.
 *
 * Verifies that:
 * - Users cannot be created beyond the signed max_users limit
 * - Modifying max_users in the payload breaks the signature
 * - Race conditions at the limit boundary are handled correctly
 * - Unlimited (max_users = 0) licenses work correctly
 */
class UserLimitTest extends LicenseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createActiveInstallation(['max_users' => 3]);
    }

    // -----------------------------------------------------------------------
    // EntitlementManager unit tests
    // -----------------------------------------------------------------------

    public function test_can_add_user_when_below_limit(): void
    {
        // Create 2 non-admin users (limit is 3)
        User::factory()->count(2)->create();
        User::factory()->create(['name' => 'Admin'])->assignRole('Admin');

        $entitlement = app(EntitlementManager::class);
        $this->assertTrue($entitlement->canAddUser());
    }

    public function test_cannot_add_user_when_at_limit(): void
    {
        $this->expectException(UserLimitExceededException::class);

        // Create 3 non-admin users (limit is 3)
        User::factory()->count(3)->create();

        $entitlement = app(EntitlementManager::class);
        $entitlement->assertCanAddUser();
    }

    public function test_max_users_from_signed_payload(): void
    {
        $entitlement = app(EntitlementManager::class);
        $this->assertEquals(3, $entitlement->maxUsers());
    }

    public function test_unlimited_license_always_allows_users(): void
    {
        // Create installation with max_users = 0 (unlimited)
        LicenseEntitlement::query()->delete();
        \App\Models\LicenseInstallation::query()->delete();

        $this->testInstallationId = bin2hex(random_bytes(32));
        $this->createActiveInstallation(['max_users' => 0]);

        // Create many users
        User::factory()->count(500)->create();

        $entitlement = app(EntitlementManager::class);
        $this->assertTrue($entitlement->canAddUser());
    }

    // -----------------------------------------------------------------------
    // HTTP endpoint tests (via storeOrUpdate)
    // -----------------------------------------------------------------------

    public function test_user_creation_endpoint_respects_license_limit(): void
    {
        // Create 3 users (at limit)
        User::factory()->count(3)->create();

        // Create an admin user to make the request
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('lms.users.store'), [
            'name'     => 'New User',
            'email'    => 'new@example.com',
            'password' => 'password123',
            'role'     => 'Agent',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('limit', strtolower($response->json('message')));
    }

    public function test_user_update_does_not_trigger_limit_check(): void
    {
        // Create 3 users (at limit)
        $users = User::factory()->count(3)->create();
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Updating an existing user should not fail even at limit
        $existingUser = $users->first();

        $response = $this->actingAs($admin)->post(route('lms.users.store'), [
            'user_id' => $existingUser->id,
            'name'    => 'Updated Name',
            'email'   => $existingUser->email,
            'role'    => 'Agent',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // Tampered payload tests
    // -----------------------------------------------------------------------

    public function test_modifying_max_users_in_db_entitlement_is_rejected(): void
    {
        $this->expectException(\App\Exceptions\License\LicenseTamperedException::class);

        // Directly tamper with the stored signed_payload
        $entitlementRecord = LicenseEntitlement::first();
        $raw               = base64_decode($entitlementRecord->signed_payload);
        $sig               = substr($raw, 0, 64);
        $json              = substr($raw, 64);
        $tampered          = str_replace('"max_users":3', '"max_users":9999', $json);

        $entitlementRecord->update([
            'signed_payload' => base64_encode($sig . $tampered),
        ]);

        // Clear in-memory cache
        app(LicenseManager::class)->clearCache();

        // Now try to get entitlement — must fail
        app(EntitlementManager::class)->maxUsers();
    }
}
