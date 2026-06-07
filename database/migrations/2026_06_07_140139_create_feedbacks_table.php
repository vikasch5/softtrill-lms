<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('added_by')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->foreign('parent_id')
                ->references('id')
                ->on('feedbacks')
                ->onDelete('cascade');
            $table->index('tenant_id');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};