<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_application_field_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('job_template_field_id')->constrained('job_template_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['job_application_id', 'job_template_field_id'], 'job_app_field_answers_unique');
            $table->index('job_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_application_field_answers');
    }
};
