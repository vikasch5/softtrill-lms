<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('dialer_lead_id')
                ->after('lead_id')
                ->nullable()
                ->index();

            $table->timestamp('assigned_at')
                ->after('assigned_to')
                ->nullable();

            $table->string('assignment_type')
                ->after('assigned_at')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'dialer_lead_id',
                'assigned_at',
                'assignment_type',
            ]);
        });
    }
};