<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\ServiceOfferOrderDisputeStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_offer_order_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_order_id')
                ->constrained('service_offer_orders')
                ->cascadeOnDelete();

            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('service_providers')->cascadeOnDelete();

            $table->enum('opened_by', ['buyer', 'provider']);

            $table->foreignId('reason_id')
                ->constrained('dispute_reasons');

            $table->text('description')->nullable();

            $table->enum('status', ServiceOfferOrderDisputeStatus::values())
            ->default(ServiceOfferOrderDisputeStatus::Open->value);

            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['service_offer_order_id', 'status']);
            $table->index('opened_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_offer_order_disputes');
    }
};
