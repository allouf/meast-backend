<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Tags are per-job; pivot links tags to applications (candidates).
     */
    public function up(): void
    {
        if (! Schema::hasTable('job_application_tags')) {
            Schema::create('job_application_tags', function (Blueprint $table) {
                $table->id();
                
                $table->foreignId('job_id')->constrained('job_postings')->cascadeOnDelete();
                $table->string('name', 64);
                $table->string('color', 16); 
                $table->timestamps();

                $table->unique(['job_id', 'name']);
                $table->index('job_id');
            });
        }

        if (! Schema::hasTable('job_application_tag_pivot')) {
            Schema::create('job_application_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('job_application_tag_id')->constrained('job_application_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['job_application_id', 'job_application_tag_id'], 'job_app_tag_pivot_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_application_tag_pivot');
        Schema::dropIfExists('job_application_tags');
    }
};
