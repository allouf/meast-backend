<?php

use Modules\Job\Enums\ApplicationStatus;
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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('job_postings')->cascadeOnDelete();

            $table->string('status', 32)->default(ApplicationStatus::New->value);
            $table->string('resume_path');
            $table->longText('cover_letter')->nullable(); // cover letter text
            $table->text('rejection_reason')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('rating_note')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'job_id']);
            $table->index('status');
            $table->index('job_id');
            $table->index('candidate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
