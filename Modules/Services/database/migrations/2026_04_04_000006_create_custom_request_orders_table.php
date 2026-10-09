<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\CustomRequestOrderPaymentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_request_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('custom_request_quotes')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('service_providers')->cascadeOnDelete();
            $table->decimal('service_price', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('fees_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method');
            $table->enum('payment_status', CustomRequestOrderPaymentStatus::values())
                ->default(CustomRequestOrderPaymentStatus::PendingPayment->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_request_orders');
    }
};
