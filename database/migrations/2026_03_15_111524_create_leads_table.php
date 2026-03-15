<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {

            $table->id();

            // Reference import file
            $table->foreignId('lead_import_file_id')
                ->nullable()
                ->constrained('lead_import_files')
                ->nullOnDelete();

            // Basic fields
            $table->string('name')->nullable();
            $table->string('phone')->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();

            // Lead management
            $table->string('status')->default('new');

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Dynamic fields
            $table->json('custom_fields')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index(['phone', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};