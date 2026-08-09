<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('added_by')->index();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('heading');
            $table->text('description')->nullable();
            $table->text('image')->nullable();
            $table->text('url')->nullable();

            $table->boolean('status')->default(true)->index();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};