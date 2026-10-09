<?php

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
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_order_id')
                ->constrained('master_orders')
                ->cascadeOnDelete();
            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_address_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->enum('shipping_method', ['standard', 'express', 'pickup'])
                ->default('standard');
            $table->enum('payment_method', ['paypal', 'stripe', 'cod', 'bank_transfer'])
                ->default('cod');
            $table->enum('status', [
                'placed',
                'seller_confirmed',
                'in_progress',
                'fulfilled',
                'completed',
                'cancelled_by_seller',
                'cancelled_timeout',
                'return_open',
                'return_approved',
                'return_declined',
                'return_resolved'
            ])->default('placed');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
