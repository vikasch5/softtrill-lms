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
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('added_by')->nullable()->index();

            $table->unsignedBigInteger('tenant_id');

            $table->unsignedBigInteger('lead_id');

            $table->unsignedBigInteger('user_id');

            $table->longText('note');

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('lead_id');
            $table->index('user_id');

            $table->index(['tenant_id', 'lead_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
    }
};
