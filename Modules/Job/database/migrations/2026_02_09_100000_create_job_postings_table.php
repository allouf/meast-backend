<?php

use Modules\Job\Enums\JobStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job postings table.
 * Named "job_postings" to avoid conflict with Laravel's queue "jobs" table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            // Ownership
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('job_categories')->cascadeOnDelete();

            // Basic Info
            $table->string('title');

            // Employment Details
            $table->string('employment_type', 32);
            $table->string('experience_level', 32);

            // Location
            $table->string('country_code', 2);
            $table->string('region')->nullable();

            // Work Mode
            $table->string('work_mode', 32);

            // Description
            $table->longText('description');

            // Salary (validation: salary_min <= salary_max in application layer)
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->boolean('hide_salary')->default(false);
            $table->string('currency', 3)->nullable()->index();

            // Dates
            $table->date('application_deadline')->nullable();

            // Status
            $table->string('status', 32)->default(JobStatus::DRAFT->value);

            // External Apply
            $table->string('application_link')->nullable();

            // Template
            $table->foreignId('job_template_id')->nullable()->constrained('job_templates')->nullOnDelete();


            $table->timestamps();

            // Indexes
            $table->index('employment_type');
            $table->index('experience_level');
            $table->index('work_mode');
            $table->index('status');
            $table->index('country_code');
            $table->index('employer_id');
            $table->index('category_id');
        });

        // Note: salary_min <= salary_max when both exist is enforced in application validation (FormRequest).
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
