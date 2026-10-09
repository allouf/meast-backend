<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Append-only audit trail for every change to a wallet's balances.
     *
     * Each row records:
     *   - the wallet affected
     *   - the type of change (credit / debit / lock / unlock)
     *   - the amount
     *   - the balance values BEFORE and AFTER the change (denormalized for
     *     fast queries and dispute investigation)
     *   - an optional polymorphic reference to the originating entity
     *     (Order / Payment / Refund / etc.)
     *   - free-form metadata as JSON (gateway tx id, blockchain hash, etc.)
     *
     * This table is never updated or deleted in normal operation.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->cascadeOnDelete();

            // Stored as a string column (not an enum) so the WalletTransactionType
            // PHP enum can be extended without an ALTER TABLE migration.
            // Allowed values are validated in the application layer.
            $table->string('type', 16);

            $table->decimal('amount', 24, 8);
            $table->decimal('balance_before', 24, 8);
            $table->decimal('balance_after', 24, 8);

            // Polymorphic reference to the source entity, if any.
            // Examples: ('App\\Models\\Order', 1234), ('Payment', 99).
            // Both nullable so wallet adjustments without an order (manual
            // top-ups, admin corrections) are also supported.
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Free-form context. Useful for gateway transaction ids, blockchain
            // hashes, idempotency keys, etc.
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Composite index for the common access pattern: "show me all
            // transactions for this wallet, newest first".
            $table->index(['wallet_id', 'created_at']);

            // Polymorphic lookup index: "find the transactions tied to Order #1234".
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
