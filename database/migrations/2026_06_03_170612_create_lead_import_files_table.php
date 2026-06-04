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
            $table->unsignedBigInteger('added_by')->nullable()->index();

            $table->unsignedBigInteger('tenant_id');

            $table->unsignedBigInteger('list_id');

            $table->string('file_name');

            $table->string('original_name');

            $table->integer('total_records')->default(0);

            $table->integer('imported_records')->default(0);

            $table->integer('failed_records')->default(0);

            $table->string('status', 50)->default('processing');

            $table->unsignedBigInteger('uploaded_by');

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('list_id');
            $table->index('status');
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
