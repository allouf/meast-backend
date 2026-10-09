<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('company_name');
            $table->string('job_title');
            $table->string('industry')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedInteger('duration_months')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_work_experiences');
    }
};

