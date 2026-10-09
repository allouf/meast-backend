<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\ServiceOfferOrderExtensionStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_order_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_order_id')
                ->constrained('service_offer_orders')
                ->cascadeOnDelete();
            $table->foreignId('reason_id')
                ->constrained('service_offer_order_extension_reasons')
                ->restrictOnDelete();
            $table->unsignedInteger('requested_days');
            $table->text('description')->nullable();
            $table->enum('status', ServiceOfferOrderExtensionStatus::values())
                ->default(ServiceOfferOrderExtensionStatus::Pending->value);
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_order_extensions');
    }
};
