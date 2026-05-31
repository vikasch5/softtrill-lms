<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('list_leads', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            // Relations
            $table->foreignId('list_id')
                ->constrained('lists')
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->constrained('leads')
                ->cascadeOnDelete();

            // Per-list (campaign) data
            $table->string('status')->default('new')->index();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->index();

            $table->integer('call_attempts')->default(0);
            $table->timestamp('last_call_time')->nullable();
            $table->timestamp('follow_up_date')->nullable()->index();

            // Per-list custom data
            $table->json('custom_data')->nullable();

            // Optional scoring / priority
            $table->integer('score')->default(0)->index();

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate in same list
            $table->unique(['list_id', 'lead_id']);

            // Performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_leads');
    }
};
