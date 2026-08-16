<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the license_security_log table.
 *
 * Immutable audit log of all license-related security events.
 * Never expose details from this table to end users.
 * Rotate/archive old records on the license server side.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_security_log', function (Blueprint $table) {
            $table->id();

            // Which installation triggered this event (nullable for pre-activation events)
            $table->char('installation_id', 64)->nullable();

            // Event type — always use string constants from LicenseSecurityLog::EVENT_*
            $table->string('event_type', 100);

            // Severity level
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');

            // Structured details (JSON) — never include sensitive secrets here
            $table->json('details')->nullable();

            // Network context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            // Immutable — use created_at only, no updated_at
            $table->timestamp('created_at')->useCurrent();

            // Indexes for querying
            $table->index('installation_id');
            $table->index('event_type');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_security_log');
    }
};
