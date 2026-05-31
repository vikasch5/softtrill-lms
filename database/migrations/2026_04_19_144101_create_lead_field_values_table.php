<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->index();
            $table->unsignedBigInteger('field_id')->index();
            $table->string('value_string')->nullable();
            $table->bigInteger('value_number')->nullable();
            $table->dateTime('value_date')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamps();
            $table->index(['field_id', 'value_string']);
            $table->index(['field_id', 'value_number']);
            $table->index(['field_id', 'value_date']);
            $table->index(['lead_id', 'field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_field_values');
    }
};