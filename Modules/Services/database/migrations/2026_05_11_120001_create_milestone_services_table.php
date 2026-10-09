<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\MilestoneServiceStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->restrictOnDelete();
            $table->string('service_name');
            $table->unsignedInteger('duration_days');
            $table->decimal('budget', 12, 2);
            $table->unsignedInteger('revision_window_days')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', MilestoneServiceStatus::values())
                ->default(MilestoneServiceStatus::Pending->value);
            $table->timestamps();

            $table->index('milestone_id');
            $table->index(['milestone_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_services');
    }
};
