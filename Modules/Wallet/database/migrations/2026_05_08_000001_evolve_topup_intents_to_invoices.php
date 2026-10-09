<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Evolves the existing `wallet_topup_intents` table into a generic `invoices`
 * table that any module can use for any kind of platform-fee or marketplace
 * payment.
 *
 * Why this is a rename + extend, not a new table:
 *   `wallet_topup_intents` is structurally already an Invoice — it has user_id,
 *   amount, currency, status, the provider's session id, completion timestamp.
 *   Building a parallel `invoices` table would duplicate the schema and create
 *   a two-paths-coexisting transition. Cleaner to rename and add the columns
 *   that generalise the table for non-topup flows.
 *
 * What changes for existing rows:
 *   - `kind` is set to 'wallet_topup' (every existing row was a top-up).
 *   - `rail` is set to 'stripe' (Stripe was the only top-up rail).
 *   - `partner_reference_id` is backfilled from `stripe_session_id`.
 *   - `metadata` is populated with `{stripe_payment_intent_id: <existing value>}`
 *     for rows that had a payment intent recorded.
 *   - All other new columns are NULL (referenceable, seller_user_id, etc. don't
 *     apply to user→platform top-ups).
 *
 * Down() reverses every step so dev iteration is reversible. ALTER TABLE on
 * MySQL is not transactional, so a failure mid-migration leaves the table in
 * an inconsistent state — fine for dev (drop and re-run); for production
 * deployment we'd run during a maintenance window.
 */
return new class extends Migration {
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // Step 1 — add new columns alongside the existing ones.
        // ─────────────────────────────────────────────────────────────────
        Schema::table('wallet_topup_intents', function (Blueprint $table) {
            // Type of payment. Snake_case, scoped per module ('wallet_topup',
            // 'service_order', 'ad_package', etc.). See payment-integration-contract.md.
            $table->string('kind', 64)->default('wallet_topup')->after('id');

            // For marketplace flow: who receives the funds. NULL = user→platform
            // (subs, ads, top-ups). SET = marketplace (split via Stripe Connect).
            $table->foreignId('seller_user_id')->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            // Polymorphic pointer back to the calling module's domain model
            // (ServiceOrder, AdCampaign, etc.). NULL is acceptable when the
            // invoice itself is the source of truth (e.g. wallet_topup).
            $table->string('referenceable_type')->nullable()->after('seller_user_id');
            $table->unsignedBigInteger('referenceable_id')->nullable()->after('referenceable_type');

            // Computed at rail-time. Platform fee + seller payout = amount
            // (minus rail's own processing fee, which Stripe takes off the top).
            $table->decimal('platform_fee_amount', 24, 8)->nullable()->after('currency');
            $table->decimal('seller_payout_amount', 24, 8)->nullable()->after('platform_fee_amount');

            // Which provider handled this invoice. Strings (not enums) so we
            // can add new rails without ALTER TABLE: 'stripe', 'stripe_connect',
            // 'platform_credit', 'nowpayments', 'tap', 'checkout_com', etc.
            $table->string('rail', 32)->default('stripe')->after('status');

            // The rail's primary handle for this invoice — Stripe checkout
            // session id, NOWPayments invoice id, etc. Replaces the
            // Stripe-specific `stripe_session_id` column we'll drop below.
            // Unique across (rail, partner_reference_id) — see composite index.
            $table->string('partner_reference_id')->nullable()->after('rail');

            // Where the user gets redirected to complete payment. NULL for
            // instant rails (platform_credit) where no redirect is needed.
            $table->text('checkout_url')->nullable()->after('partner_reference_id');

            // Caller-supplied retry-safe key. Two `initiate()` calls with the
            // same key return the same invoice instead of creating a duplicate.
            $table->string('idempotency_key', 64)->nullable()->unique()->after('checkout_url');

            // Lifecycle timestamps. completed_at already exists.
            $table->timestamp('expires_at')->nullable()->after('completed_at');
            $table->timestamp('failed_at')->nullable()->after('expires_at');
            $table->timestamp('refunded_at')->nullable()->after('failed_at');

            // Free-form rail-specific data: stripe_payment_intent_id,
            // nowpayments_deposit_address, etc. JSON keeps the top-level
            // schema rail-agnostic while preserving every field a rail emits.
            $table->json('metadata')->nullable()->after('refunded_at');

            // New indexes for the access patterns we'll see:
            //   - "what invoices does this seller have, by status?" (marketplace)
            //   - "what invoice goes with this domain object?" (cross-module)
            $table->index(['seller_user_id', 'status']);
            $table->index(['referenceable_type', 'referenceable_id']);
            // Composite uniqueness on (rail, partner_reference_id) — same
            // session id from different rails wouldn't collide. Adding via
            // raw SQL because Laravel's unique() with a name hits length
            // limits when the column names are long.
        });

        // Composite unique index that allows multiple NULLs but rejects
        // duplicate (rail, partner_reference_id) combos once both are set.
        DB::statement(
            'CREATE UNIQUE INDEX uq_wallet_topup_intents_rail_partner_ref '
            . 'ON wallet_topup_intents (rail, partner_reference_id)',
        );

        // ─────────────────────────────────────────────────────────────────
        // Step 2 — backfill new columns from old ones.
        // ─────────────────────────────────────────────────────────────────
        // Existing rows are all top-ups via Stripe. The defaults set above
        // already give them kind='wallet_topup' and rail='stripe' — we just
        // need to copy the Stripe identifiers across.

        // Copy stripe_session_id → partner_reference_id (one-to-one rename).
        DB::statement(
            'UPDATE wallet_topup_intents '
            . 'SET partner_reference_id = stripe_session_id '
            . 'WHERE stripe_session_id IS NOT NULL',
        );

        // Move stripe_payment_intent_id into metadata as a JSON field. We
        // don't keep this as a top-level column going forward — it's
        // Stripe-specific and other rails won't have an analogue.
        DB::statement(
            'UPDATE wallet_topup_intents '
            . 'SET metadata = JSON_OBJECT("stripe_payment_intent_id", stripe_payment_intent_id) '
            . 'WHERE stripe_payment_intent_id IS NOT NULL',
        );

        // ─────────────────────────────────────────────────────────────────
        // Step 3 — drop the now-superseded Stripe-specific columns.
        // ─────────────────────────────────────────────────────────────────
        Schema::table('wallet_topup_intents', function (Blueprint $table) {
            // Their unique indexes are auto-dropped by MySQL when the column
            // goes; no explicit dropUnique() needed.
            $table->dropColumn(['stripe_session_id', 'stripe_payment_intent_id']);
        });

        // ─────────────────────────────────────────────────────────────────
        // Step 4 — rename the table.
        // ─────────────────────────────────────────────────────────────────
        // Foreign keys pointing at this table (currently only wallet_refunds.
        // wallet_topup_intent_id) follow renames in MySQL — they continue to
        // reference the renamed table without re-pointing.
        Schema::rename('wallet_topup_intents', 'invoices');
    }

    public function down(): void
    {
        // Reverse the rename first so the rest of the rollback works on
        // the original table name.
        Schema::rename('invoices', 'wallet_topup_intents');

        // Re-add the original Stripe-specific columns as nullable. We'll
        // restore data into them from the new columns / metadata before
        // dropping the new ones.
        Schema::table('wallet_topup_intents', function (Blueprint $table) {
            $table->string('stripe_session_id')->nullable()->after('user_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_session_id')->unique();
        });

        // Restore: partner_reference_id → stripe_session_id (only when the
        // original rail was Stripe; non-Stripe rails wouldn't have had a
        // stripe_session_id to begin with).
        DB::statement(
            'UPDATE wallet_topup_intents '
            . 'SET stripe_session_id = partner_reference_id '
            . "WHERE rail = 'stripe' AND partner_reference_id IS NOT NULL",
        );

        // Restore stripe_payment_intent_id from metadata JSON.
        DB::statement(
            'UPDATE wallet_topup_intents '
            . 'SET stripe_payment_intent_id = JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.stripe_payment_intent_id")) '
            . 'WHERE metadata IS NOT NULL '
            . 'AND JSON_EXTRACT(metadata, "$.stripe_payment_intent_id") IS NOT NULL',
        );

        // Drop the composite unique index added in up().
        DB::statement('DROP INDEX uq_wallet_topup_intents_rail_partner_ref ON wallet_topup_intents');

        // Drop all the new columns and their indexes.
        Schema::table('wallet_topup_intents', function (Blueprint $table) {
            $table->dropForeign(['seller_user_id']);
            $table->dropIndex(['seller_user_id', 'status']);
            $table->dropIndex(['referenceable_type', 'referenceable_id']);

            $table->dropColumn([
                'kind',
                'seller_user_id',
                'referenceable_type',
                'referenceable_id',
                'platform_fee_amount',
                'seller_payout_amount',
                'rail',
                'partner_reference_id',
                'checkout_url',
                'idempotency_key',
                'expires_at',
                'failed_at',
                'refunded_at',
                'metadata',
            ]);
        });
    }
};
