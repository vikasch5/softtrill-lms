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
        Schema::create('lead_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('added_by')->nullable()->index();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('list_id');

            $table->string('name');
            $table->string('slug');

            $table->enum('type', [
                'text',
                'textarea',
                'email',
                'phone',
                'number',
                'decimal',
                'date',
                'datetime',
                'select',
                'checkbox',
                'radio',
                'boolean'
            ]);

            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_unique')->default(false);

            $table->json('options')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('list_id');

            $table->unique(['list_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_fields');
    }
};
