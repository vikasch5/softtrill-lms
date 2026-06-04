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
        Schema::create('lead_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('added_by')->nullable()->index();

            $table->unsignedBigInteger('tenant_id');

            $table->unsignedBigInteger('lead_id');

            $table->unsignedBigInteger('assigned_to');

            $table->dateTime('followup_at');

            $table->string('status', 50)->default('pending');

            $table->text('remarks')->nullable();

            $table->dateTime('completed_at')->nullable();

            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('lead_id');

            $table->index('assigned_to');

            $table->index('followup_at');

            $table->index('status');

            $table->index(['tenant_id', 'assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_followups');
    }
};
