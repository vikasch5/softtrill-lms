<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the license_installations table.
 *
 * This table stores the per-installation identity and API credential.
 * It is the anchor for all license operations for this LMS instance.
 *
 * Security design:
 * - installation_id is HMAC-protected (installation_hmac column) so DB tampering is detectable
 * - api_credential_hash stores bcrypt of the credential so the plain credential is only revealed once
 * - No license entitlement values are stored here — those live in license_entitlements as signed blobs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_installations', function (Blueprint $table) {
            $table->id();

            // The cryptographically random installation identifier (hex-encoded 32 bytes)
            $table->char('installation_id', 64)->unique();

            // HMAC-SHA256 of installation_id using APP_KEY-derived secret.
            // If someone changes installation_id in DB, this HMAC will no longer match.
            $table->char('installation_hmac', 64);

            // Per-installation API credential (hex-encoded 32 random bytes).
            // Used to authenticate requests to the Softtrill license server.
            // Only revealed once at activation time.
            $table->char('api_credential', 64)->unique()->nullable();

            // bcrypt hash of api_credential for server-side verification
            $table->string('api_credential_hash', 255)->nullable();

            // The domain this installation was activated for (normalized, no www)
            $table->string('domain', 255)->nullable();

            // Current activation status
            $table->enum('status', ['pending', 'active', 'deactivated'])->default('pending');

            // Timestamps for lifecycle management
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();

            // When the offline grace period expires (set when server becomes unreachable)
            $table->timestamp('grace_expires_at')->nullable();

            $table->timestamps();

            // Index for status lookups
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_installations');
    }
};
