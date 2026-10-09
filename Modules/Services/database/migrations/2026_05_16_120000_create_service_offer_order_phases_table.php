<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\MilestoneOrderPhaseStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_order_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_order_id')
                ->constrained('service_offer_orders')
                ->cascadeOnDelete();
            $table->foreignId('milestone_service_id')
                ->nullable()
                ->constrained('milestone_services')
                ->nullOnDelete();
            $table->string('service_name');
            $table->foreignId('service_category_id')
                ->constrained('service_categories')
                ->restrictOnDelete();
            $table->unsignedInteger('duration_days');
            $table->decimal('budget', 12, 2);
            $table->unsignedInteger('revision_window_days')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', MilestoneOrderPhaseStatus::values())
                ->default(MilestoneOrderPhaseStatus::PendingPayment->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['service_offer_order_id', 'sort_order'], 'soo_phases_order_sort_idx');
            $table->index(['service_offer_order_id', 'status'], 'soo_phases_order_status_idx');
        });

        Schema::table('service_offer_orders', function (Blueprint $table) {
            $table->foreign('current_phase_id', 'soo_orders_current_phase_fk')
                ->references('id')
                ->on('service_offer_order_phases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_offer_orders', function (Blueprint $table) {
            $table->dropForeign('soo_orders_current_phase_fk');
        });

        Schema::dropIfExists('service_offer_order_phases');
    }
};
