<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the license_entitlements table.
 *
 * Stores signed license payloads received from the Softtrill license server.
 * The signed_payload column contains: base64(Ed25519_signature + canonical_json_payload).
 *
 * Security design:
 * - signed_payload is verified with Ed25519 on every load — any modification is detected
 * - Values like max_users, expires_at, features are NOT stored as separate columns
 *   because a customer could change those columns directly; instead they are read only
 *   from the verified signed_payload
 * - The cached_* columns are metadata about when to re-validate, not entitlement values
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_entitlements', function (Blueprint $table) {
            $table->id();

            // Foreign key to the installation
            $table->char('installation_id', 64);
            $table->foreign('installation_id')
                ->references('installation_id')
                ->on('license_installations')
                ->onDelete('cascade');

            // The signed license blob: base64(64-byte Ed25519 signature + canonical JSON payload)
            // This is the ONLY authoritative entitlement source. All other columns are metadata.
            $table->longText('signed_payload');

            // SHA-256 hash of the canonical payload (without signature).
            // Used to quickly detect if the row was tampered before running full Ed25519 verification.
            // Note: this is a fast pre-check only; Ed25519 is always the final authority.
            $table->char('payload_hash', 64);

            // Metadata extracted from the payload for cache management (NOT authoritative)
            // These are only used to determine when to re-validate, never for entitlement decisions
            $table->string('license_id', 100)->nullable();
            $table->timestamp('payload_issued_at')->nullable();
            $table->timestamp('payload_expires_at')->nullable();
            $table->timestamp('cached_until')->nullable(); // when to next re-validate

            // Current status (mirrored from payload for quick queries — NOT authoritative)
            $table->string('cached_status', 50)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('installation_id');
            $table->index('cached_until');
            $table->index('payload_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_entitlements');
    }
};
