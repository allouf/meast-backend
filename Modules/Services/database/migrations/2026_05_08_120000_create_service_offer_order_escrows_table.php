<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Wallet\Enums\EscrowStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_order_escrows', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('escrowable', 'escrows_escrowable_idx');
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->nullOnDelete();
            $table->string('gateway');
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->enum('status', EscrowStatus::values())
                ->default(EscrowStatus::Pending->value);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_order_escrows');
    }
};
