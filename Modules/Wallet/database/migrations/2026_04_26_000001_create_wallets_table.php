<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One wallet per user. Used by Flow B (platform-fee payments) only —
     * marketplace/Flow A does not touch this table. Holds three balances:
     *   - available  : freely spendable on platform fees
     *   - pending    : incoming funds awaiting blockchain confirmations or settlement
     *   - locked     : reserved against active subscriptions / ad commitments
     *
     * Denomination unit (USD / USDT / MEEC pegged) is stored in `currency`,
     * which is a simple string for now. This is intentionally permissive while
     * the unit-of-account decision (Checklist Row #1) is open.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // Three balances, all in the same denomination unit (`currency`).
            // 24 total digits with 8 decimals supports both fiat (cents) and crypto
            // (8-decimal precision is the Bitcoin/Ethereum/USDT standard).
            $table->decimal('available_balance', 24, 8)->default(0);
            $table->decimal('pending_balance', 24, 8)->default(0);
            $table->decimal('locked_balance', 24, 8)->default(0);

            // Denomination unit. Examples: "USD", "USDT", "MEEC".
            // The wallet does not auto-convert; conversions happen at the rail edges
            // (deposit / withdrawal) by the future PaymentService.
            $table->string('currency', 16);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
