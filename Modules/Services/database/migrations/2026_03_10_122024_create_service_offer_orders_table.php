<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\ServiceOfferOrderStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('service_providers')->cascadeOnDelete();
            $table->foreignId('reference_service_offer_id')
                ->nullable()
                ->constrained('service_offers')
                ->nullOnDelete();
            $table->nullableMorphs('orderable');
            $table->string('title');
            $table->decimal('price', 12, 2);
            $table->string('currency', 3);
            $table->unsignedInteger('delivery_time_minutes')->nullable();
            $table->json('requirements')->nullable();
            $table->enum('status', ServiceOfferOrderStatus::values())
                ->default(ServiceOfferOrderStatus::PendingPayment->value);
            $table->foreignId('rejection_reason_id')
                ->nullable()
                ->constrained('service_offer_order_rejection_reasons')
                ->nullOnDelete();
            $table->text('rejection_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->unsignedBigInteger('current_phase_id')->nullable();
            $table->timestamps();

            $table->index(['buyer_id', 'provider_id', 'orderable_type', 'orderable_id'], 'idx_order_buyer_provider_orderable');
            $table->index('current_phase_id', 'soo_orders_current_phase_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_orders');
    }
};
