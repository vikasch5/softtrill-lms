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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('added_by')->nullable()->index();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('list_id');

            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->string('status', 50)->default('new');

            $table->string('email_index')->nullable();
            $table->string('phone_index')->nullable();

            $table->string('duplicate_hash', 64)->nullable();

            $table->json('data');

            $table->timestamp('last_followup_at')->nullable();
            $table->timestamp('next_followup_at')->nullable();

            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('list_id');
            $table->index('assigned_to');
            $table->index('status');

            $table->index('email_index');
            $table->index('phone_index');

            $table->index('next_followup_at');

            $table->index(['tenant_id', 'list_id']);
            $table->index(['tenant_id', 'assigned_to']);

            $table->index('duplicate_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
