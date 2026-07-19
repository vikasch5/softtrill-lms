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
        Schema::table('dashboard_widgets', function (Blueprint $table) {

            // Who created the widget
            $table->unsignedBigInteger('added_by')->nullable()->after('tenant_id');

            // Time grouping for line / area / bar charts
            $table->enum('group_by', ['day', 'week', 'month', 'year'])->nullable()->after('aggregate');

            // Bootstrap column width (3 / 4 / 6 / 12)
            $table->unsignedTinyInteger('width')->default(6)->after('sort_order');

            // Canvas height in pixels
            $table->unsignedSmallInteger('height')->default(350)->after('width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            $table->dropColumn(['added_by', 'group_by', 'width', 'height']);
        });
    }
};
