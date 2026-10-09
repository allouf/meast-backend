<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records every wallet top-up attempt initiated through Stripe (or any
     * future fiat gateway). One row is created when the user clicks "Top up";
     * the row's status is updated by the gateway webhook.
     *
     * Lifecycle of a row:
     *   pending    -> user has been redirected to Stripe Checkout
     *   completed  -> webhook confirmed payment; wallet has been credited
     *   cancelled  -> user closed the Checkout page or it expired
     *   failed     -> Stripe reported a payment failure
     *
     * The table also serves as an idempotency record: if the same Stripe
     * webhook event is delivered twice, we look up the row by stripe_session_id
     * and skip the second credit.
     */
    public function up(): void
    {
        Schema::create('wallet_topup_intents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // The wallet credited on success. Nullable until first credit so
            // the row can be created before we resolve the wallet (defensive).
            $table->foreignId('wallet_id')
                ->nullable()
                ->constrained('wallets')
                ->nullOnDelete();

            // Top-up amount in major units of `currency` (e.g. USD dollars,
            // not cents). decimal(24,8) matches the wallet's precision so
            // there is no conversion when crediting.
            $table->decimal('amount', 24, 8);

            // ISO 4217 lower-case (Stripe convention). Examples: 'usd', 'sar'.
            $table->string('currency', 8);

            // Stored as a string column (not enum) so the PHP enum can be
            // extended without an ALTER TABLE migration. Allowed values are
            // validated in the application layer.
            $table->string('status', 16);

            // Stripe identifiers — both nullable because they're populated as
            // the flow progresses (session_id is set on initiate; the
            // payment_intent_id arrives on the success webhook).
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->unique();

            // Optional reason / failure message for failed/cancelled rows.
            $table->string('failure_reason')->nullable();

            // When the webhook confirmed completion. Distinct from updated_at
            // so we can audit the state-machine transitions later.
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Common access patterns: "what's the user's recent top-up activity",
            // "find a row by status".
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_topup_intents');
    }
};
