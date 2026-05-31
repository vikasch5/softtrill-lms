<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {

            $table->id();

            // SaaS isolation (REQUIRED)
            $table->unsignedBigInteger('tenant_id');

            // Source tracking (optional but useful)
            $table->foreignId('lead_import_file_id')
                ->nullable()
                ->constrained('lead_import_files')
                ->nullOnDelete();

            // Core identity (MASTER DATA)
            $table->string('name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();

            // Basic info (global only)
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // Non-filterable extra data
            $table->json('custom_fields')->nullable();

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('phone');
            $table->index('email');
            $table->index(['tenant_id', 'created_at']);

            // VERY IMPORTANT: prevent duplicates per tenant
            $table->unique(['tenant_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};