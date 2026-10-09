<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks Transak orders we know about.
 *
 * Important: this table is NOT a custody record. We never hold the user's
 * crypto — Transak sends it directly to the user's wallet address. This
 * table exists for:
 *   - Reconciliation / analytics ("how many MEEC purchases this week")
 *   - Showing the user their order history in the platform UI
 *   - Idempotent webhook handling (unique transak_order_id stops duplicate
 *     processing if Transak retries a webhook delivery)
 *
 * Idempotency anchor: transak_order_id (unique). If the webhook fires twice,
 * the second call hits the unique constraint and we skip.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('transak_orders', function (Blueprint $table) {
            $table->id();

            // Transak's id for the order. Unique → idempotency anchor.
            $table->string('transak_order_id')->unique();

            // Our internal order/invoice id we passed to Transak as
            // partnerOrderId when signing the widget URL. Useful for joining
            // orders back to the platform-side intent that triggered them.
            // Nullable because legacy / migrated orders may not have it.
            $table->string('partner_order_id')->nullable()->index();

            // Owning user. Nullable so unknown-user webhooks (rare, but
            // possible if user data is missing) still get persisted for
            // manual triage.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Latest known status from Transak's lifecycle:
            //   AWAITING_PAYMENT_FROM_USER, PAYMENT_DONE_MARKED_BY_USER,
            //   PROCESSING, COMPLETED, EXPIRED, FAILED, CANCELLED, REFUNDED
            // We store as-received (string) to avoid drift with their enum.
            $table->string('status')->index();

            // Fiat side.
            $table->string('fiat_currency', 8)->nullable();
            $table->decimal('fiat_amount', 20, 8)->nullable();

            // Crypto side. Decimal precision must hold MEEC's 18 decimals
            // plus a margin: decimal(36, 18) is standard for ERC-20 amounts.
            $table->string('crypto_currency', 32)->nullable();
            $table->decimal('crypto_amount', 36, 18)->nullable();
            $table->string('network', 32)->nullable();

            // Where Transak will send / sent the crypto. Lower-cased for
            // EVM addresses; we don't enforce checksum because Transak
            // and various wallets format differently.
            $table->string('wallet_address')->nullable()->index();

            // Full webhook payload for audit / debugging.
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transak_orders');
    }
};
