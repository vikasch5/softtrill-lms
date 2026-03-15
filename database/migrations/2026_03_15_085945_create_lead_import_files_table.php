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
        Schema::create('lead_import_files', function (Blueprint $table) {

            $table->id();
            $table->string('file_name');
            $table->string('original_name');
            $table->integer('total_records')->nullable();
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('status', ['processing', 'completed', 'failed'])
                ->default('processing');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_import_files');
    }
};
