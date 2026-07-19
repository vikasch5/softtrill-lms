<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Composite index — covers WHERE list_id = X GROUP BY status
            $table->index(['list_id', 'status'], 'leads_list_status_idx');

            // Covers time-series GROUP BY DATE_FORMAT(created_at, ...)
            $table->index(['list_id', 'created_at'], 'leads_list_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_list_status_idx');
            $table->dropIndex('leads_list_created_idx');
        });
    }
};
