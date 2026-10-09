<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records every refund processed against a top-up. Created when the
     * gateway webhook tells us a refund occurred (we cannot refuse refunds —
     * Stripe enforces them; this table is purely a record of what happened).
     *
     * One row per individual Stripe refund. A single charge can be refunded
     * multiple times (partial refunds), each generating its own row, each
     * with its own stripe_refund_id.
     *
     * Idempotency is anchored on stripe_refund_id (unique). If Stripe
     * retries the webhook for the same refund, we recognize it and skip.
     */
    public function up(): void
    {
        Schema::create('wallet_refunds', function (Blueprint $table) {
            $table->id();

            // Link back to the top-up that's being refunded. Cascade-on-delete
            // is fine: if the original top-up record is wiped (admin cleanup),
            // the refund record goes with it.
            $table->foreignId('wallet_topup_intent_id')
                ->constrained('wallet_topup_intents')
                ->cascadeOnDelete();

            // Denormalized for fast "refunds for this wallet" queries.
            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->cascadeOnDelete();

            // Refund amount in major units of `currency` (e.g. USD dollars).
            // Always positive; the direction is implied by the entity.
            $table->decimal('amount', 24, 8);
            $table->string('currency', 8);

            // The Stripe Refund id (re_...). Unique — drives idempotency.
            $table->string('stripe_refund_id')->unique();

            // The original Charge that was refunded (ch_...). Useful for
            // dashboard cross-referencing and support tickets.
            $table->string('stripe_charge_id')->nullable();

            // Stripe's reason code for the refund: 'duplicate', 'fraudulent',
            // 'requested_by_customer', or null. Useful for fraud reporting.
            $table->string('reason')->nullable();

            // The wallet_transactions row id we wrote when applying the refund.
            // Allows direct navigation from a refund record to its audit row.
            $table->foreignId('wallet_transaction_id')
                ->nullable()
                ->constrained('wallet_transactions')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index('wallet_topup_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_refunds');
    }
};
