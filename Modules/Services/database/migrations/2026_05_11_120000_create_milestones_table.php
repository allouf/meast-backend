<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Enums\MilestoneStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')
                ->nullable()
                ->constrained('service_providers')
                ->cascadeOnDelete();
            $table->foreignId('service_offer_order_id')
                ->nullable()
                ->constrained('service_offer_orders')
                ->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('delivery_model', DeliveryModel::values())
                ->default(DeliveryModel::Remote->value);
            $table->decimal('total_budget', 12, 2)->default(0);
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('request_modification_days');
            $table->enum('status', MilestoneStatus::values())
                ->default(MilestoneStatus::Pending->value);
            $table->boolean('activation_status')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_provider_id', 'activation_status']);
            $table->index('activation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
