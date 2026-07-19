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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('list_id')->nullable();
            $table->string('title');
            $table->unsignedBigInteger('field_id')->nullable();
            $table->enum('chart_type', [
                'card',
                'bar',
                'line',
                'pie',
                'doughnut',
                'area'
            ]);
            $table->enum('aggregate', [
                'count',
                'sum',
                'avg',
                'min',
                'max'
            ])->default('count');
            $table->json('filters')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
