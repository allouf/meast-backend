<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks each seller's Stripe Connect account state.
 *
 * One row per (user, Connect account). Currently we limit each user to a
 * single Connect account (unique constraint on user_id) — a reasonable v1
 * simplification. If a seller ever needs multiple accounts (different
 * countries / brands), drop the unique constraint and add a name column.
 *
 * Lifecycle:
 *   1. POST /api/v1/seller/stripe-onboarding creates a Stripe Account
 *      via the Stripe API and inserts a row here with stripe_account_id +
 *      defaults (details_submitted=false, charges_enabled=false, etc.).
 *   2. Same endpoint generates a Stripe-hosted Account Link URL; seller
 *      is redirected and completes their KYB on Stripe's side.
 *   3. As the seller progresses, Stripe fires `account.updated` webhooks.
 *      Our webhook handler syncs `details_submitted` / `charges_enabled` /
 *      `payouts_enabled` based on the latest payload.
 *   4. StripeConnectRail::supports() refuses to handle a marketplace
 *      invoice for a seller whose row here has `charges_enabled=false`.
 *      Frontend should redirect such sellers back through onboarding.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seller_stripe_accounts', function (Blueprint $table) {
            $table->id();

            // The platform user this Connect account belongs to. Unique —
            // one Connect account per user for v1. Cascade because if the
            // user is deleted, the Connect record is meaningless (the
            // Stripe-side account would also need cleanup, separately).
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Stripe's id for the connected account (acct_xxx). Unique
            // because each Connect account is referenced as a destination
            // by exactly one row here.
            $table->string('stripe_account_id')->unique();

            // KYB lifecycle flags from Stripe. Mirror the booleans of
            // the same name on Stripe's Account object. We resync these
            // whenever account.updated fires.
            $table->boolean('details_submitted')->default(false);
            $table->boolean('charges_enabled')->default(false);
            $table->boolean('payouts_enabled')->default(false);

            // Optional metadata Stripe gives us at account creation.
            // Useful for audit / fraud review but not load-bearing.
            $table->string('country', 2)->nullable();
            $table->string('default_currency', 8)->nullable();

            // Account flavour. Phase 4a uses 'express' (Stripe-hosted
            // onboarding, simplest). 'standard' / 'custom' would require
            // different onboarding flows; left as a string so we can
            // record it without ALTER TABLE later.
            $table->string('account_type', 16)->default('express');

            // When we last synced state from Stripe (either via webhook
            // or on-demand). NULL until the first sync. Useful for
            // detecting drift if we suspect webhooks are missing.
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // One Connect account per user (v1 simplification — drop if
            // multi-account becomes a real requirement).
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_stripe_accounts');
    }
};
