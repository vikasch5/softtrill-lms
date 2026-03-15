<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {

            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('employee_id')->nullable();
            $table->string('phone',20)->nullable();
            $table->string('profile_photo')->nullable();
            $table->foreignId('cluster_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('teamleader_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->date('joining_date')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_login')->nullable();
            $table->timestamp('last_logout')->nullable();
            $table->timestamps();
            $table->index('phone');
            $table->index('cluster_id');
            $table->index('manager_id');
            $table->index('teamleader_id');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};