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
            $table->string('list_code')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('list_name');
            $table->string('file_name');
            $table->string('original_name');
            $table->integer('total_records')->default(0);
            $table->integer('imported_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->enum('status', ['processing', 'completed', 'failed'])
                ->default('processing')
                ->index();
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
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
