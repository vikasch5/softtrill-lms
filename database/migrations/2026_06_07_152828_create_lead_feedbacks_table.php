<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_feedbacks', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lead_id');

            $table->unsignedBigInteger('feedback_id');
            $table->unsignedBigInteger('sub_feedback_id')->nullable();

            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();

            $table->dateTime('followup_date')->nullable();

            // pending, completed, cancelled, missed
            $table->string('status', 50)->default('pending');

            $table->text('remarks')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('tenant_id');
            $table->index('lead_id');

            $table->index('feedback_id');
            $table->index('sub_feedback_id');

            $table->index('assigned_to');
            $table->index('added_by');

            $table->index('status');
            $table->index('followup_date');

            $table->index(['tenant_id', 'lead_id']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['assigned_to', 'status']);
            $table->index(['followup_date', 'status']);

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->cascadeOnDelete();

            $table->foreign('feedback_id')
                ->references('id')
                ->on('feedbacks')
                ->cascadeOnDelete();

            $table->foreign('sub_feedback_id')
                ->references('id')
                ->on('feedbacks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_feedbacks');
    }
};