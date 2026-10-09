<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_template_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('type'); // text, number, date, file, select, multi-select, etc.
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable(); // for select / multi-select
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->index('job_template_id');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_template_fields');
    }
};
