<?php

/**
 * =============================================================================
 * SOFTTRILL LICENSE SERVER — Database Migrations
 * =============================================================================
 *
 * These migrations are for the CENTRAL Softtrill License Server,
 * NOT for the customer's LMS installation.
 *
 * The license server is a separate Laravel application that holds
 * the Ed25519 PRIVATE KEY and has sole authority to issue signed payloads.
 *
 * File: database/migrations/0001_create_license_server_tables.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // licenses — master license records
        // ---------------------------------------------------------------
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('license_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->string('product', 50)->default('softtrill-lms');
            $table->string('license_key', 100)->unique();
            $table->enum('status', ['active', 'suspended', 'revoked', 'expired'])->default('active');
            $table->unsignedInteger('max_users')->default(10);
            $table->unsignedInteger('max_activations')->default(1);
            $table->json('features')->nullable();         
            $table->timestamp('expires_at');
            $table->timestamp('issued_at')->useCurrent();
            $table->string('plan_name', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
            $table->index('product');
        });

        // ---------------------------------------------------------------
        // license_activations — one row per LMS installation
        // ---------------------------------------------------------------
        Schema::create('license_activations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('license_id');
            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('cascade');
            $table->char('installation_id', 64)->unique();    // hex-encoded random ID
            $table->char('api_credential_hash', 255);         // bcrypt of installation API credential
            $table->string('domain', 255);                    // normalized domain
            $table->string('ip_address', 45)->nullable();
            $table->string('php_version', 20)->nullable();
            $table->enum('status', ['active', 'deactivated', 'revoked'])->default('active');
            $table->timestamp('activated_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivation_reason', 255)->nullable();
            $table->timestamps();

            $table->index('license_id');
            $table->index('status');
            $table->index('domain');
        });

        // ---------------------------------------------------------------
        // license_events — immutable audit log
        // ---------------------------------------------------------------
        Schema::create('license_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('license_id')->nullable();
            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('set null');
            $table->char('installation_id', 64)->nullable();
            $table->string('event_type', 100);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('license_id');
            $table->index('installation_id');
            $table->index('event_type');
        });

        // ---------------------------------------------------------------
        // license_nonces — replay prevention for API requests
        // ---------------------------------------------------------------
        Schema::create('license_nonces', function (Blueprint $table) {
            $table->id();
            $table->char('installation_id', 64);
            $table->char('nonce', 64)->unique(); // hex-encoded
            $table->timestamp('used_at')->useCurrent();
            $table->timestamp('expires_at');

            $table->index(['installation_id', 'nonce']);
            $table->index('expires_at'); // for cleanup jobs
        });

        // ---------------------------------------------------------------
        // license_api_keys — global API keys for admin access
        // ---------------------------------------------------------------
        Schema::create('license_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->char('key_hash', 255); // bcrypt of the API key
            $table->json('abilities')->nullable(); // ["activate","validate","revoke"]
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        // ---------------------------------------------------------------
        // signing_keys — track keypair versions for rotation
        // ---------------------------------------------------------------
        Schema::create('signing_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_id', 50)->unique(); // e.g. "kid-2026-01"
            $table->text('public_key_base64');       // the public half (safe to store)
            // NEVER store the private key in the database — use a hardware key or vault
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('retired_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_keys');
        Schema::dropIfExists('license_api_keys');
        Schema::dropIfExists('license_nonces');
        Schema::dropIfExists('license_events');
        Schema::dropIfExists('license_activations');
        Schema::dropIfExists('licenses');
    }
};
