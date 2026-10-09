<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Evolves `wallet_refunds` into a generic `invoice_refunds` table.
 *
 * Same approach as the previous migration: rename + extend, no parallel
 * new table. The existing schema already does most of what we want — it
 * just has Stripe-specific column names. We rename them, move
 * Stripe-specific extras into a metadata JSON column, and rename the
 * table. The column `wallet_topup_intent_id` becomes `invoice_id` since
 * the table it references has been renamed `wallet_topup_intents` →
 * `invoices` by the previous migration.
 *
 * Foreign-key handling: MySQL preserves a FK across a target-table rename,
 * but we still drop and re-add it here so the constraint name is sensible
 * and the column rename doesn't leave a dangling old name in the FK.
 */
return new class extends Migration {
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // Step 1 — drop the existing FK so we can rename its column.
        // ─────────────────────────────────────────────────────────────────
        Schema::table('wallet_refunds', function (Blueprint $table) {
            $table->dropForeign(['wallet_topup_intent_id']);
        });

        // ─────────────────────────────────────────────────────────────────
        // Step 2 — rename columns + add new ones.
        // ─────────────────────────────────────────────────────────────────
        Schema::table('wallet_refunds', function (Blueprint $table) {
            // Old name reflected the topup-only origin; new name reflects
            // that any kind of invoice (service order, ad, topup, etc.)
            // can have refunds.
            $table->renameColumn('wallet_topup_intent_id', 'invoice_id');

            // Stripe-specific name → rail-agnostic name. partner_refund_id
            // works for any rail (Stripe refund id, NOWPayments refund id, etc.).
            $table->renameColumn('stripe_refund_id', 'partner_refund_id');

            // Free-form rail-specific data (e.g. stripe_charge_id stays
            // here under {"stripe_charge_id": "ch_..."}).
            $table->json('metadata')->nullable()->after('reason');
        });

        // ─────────────────────────────────────────────────────────────────
        // Step 3 — backfill metadata from the Stripe-specific column.
        // ─────────────────────────────────────────────────────────────────
        DB::statement(
            'UPDATE wallet_refunds '
            . 'SET metadata = JSON_OBJECT("stripe_charge_id", stripe_charge_id) '
            . 'WHERE stripe_charge_id IS NOT NULL',
        );

        // ─────────────────────────────────────────────────────────────────
        // Step 4 — drop the now-redundant Stripe-specific column.
        // ─────────────────────────────────────────────────────────────────
        Schema::table('wallet_refunds', function (Blueprint $table) {
            $table->dropColumn('stripe_charge_id');
        });

        // ─────────────────────────────────────────────────────────────────
        // Step 5 — rename the table and re-add the FK with the new column name.
        // ─────────────────────────────────────────────────────────────────
        Schema::rename('wallet_refunds', 'invoice_refunds');

        Schema::table('invoice_refunds', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')->on('invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Reverse: drop the new FK, rename the table back, restore columns,
        // re-add the old FK.
        Schema::table('invoice_refunds', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::rename('invoice_refunds', 'wallet_refunds');

        Schema::table('wallet_refunds', function (Blueprint $table) {
            $table->string('stripe_charge_id')->nullable()->after('reason');
        });

        // Restore stripe_charge_id from metadata.
        DB::statement(
            'UPDATE wallet_refunds '
            . 'SET stripe_charge_id = JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.stripe_charge_id")) '
            . 'WHERE metadata IS NOT NULL '
            . 'AND JSON_EXTRACT(metadata, "$.stripe_charge_id") IS NOT NULL',
        );

        Schema::table('wallet_refunds', function (Blueprint $table) {
            $table->dropColumn('metadata');
            $table->renameColumn('partner_refund_id', 'stripe_refund_id');
            $table->renameColumn('invoice_id', 'wallet_topup_intent_id');
        });

        // Important rollback ordering note: migration 1's down() runs AFTER
        // this one and is what renames `invoices` back to `wallet_topup_intents`.
        // At THIS point in rollback, the parent table is still called
        // `invoices`. We point the FK there; MySQL preserves the FK across
        // the upcoming rename. By the time the full rollback chain finishes,
        // `wallet_refunds.wallet_topup_intent_id` correctly references
        // `wallet_topup_intents.id`.
        Schema::table('wallet_refunds', function (Blueprint $table) {
            $table->foreign('wallet_topup_intent_id')
                ->references('id')->on('invoices')
                ->cascadeOnDelete();
        });
    }
};
