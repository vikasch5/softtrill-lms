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
        Schema::table('license_installations', function (Blueprint $table) {
            $table->unsignedBigInteger('last_server_time')->nullable()->after('grace_expires_at');
            $table->timestamp('last_successful_validation_at')->nullable()->after('last_server_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_installations', function (Blueprint $table) {
            $table->dropColumn(['last_server_time', 'last_successful_validation_at']);
        });
    }
};
