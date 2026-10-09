<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rail-agnostic webhook deduplication anchor.
 *
 * Every payment provider eventually retries webhook deliveries (Stripe is
 * known to deliver the same event multiple times in some scenarios). The
 * usual idempotency strategy is "if we've seen this provider event id
 * before, ack 200 and skip processing." This table is that "if we've seen
 * this id before" lookup, separated from the invoice itself so we can
 * dedupe events that don't have a 1:1 relationship to an invoice (e.g.
 * account.updated webhooks for Stripe Connect onboarding state changes).
 *
 * Workflow inside a webhook handler:
 *   1. Verify signature (rail-specific).
 *   2. Try INSERT into webhook_events with (rail, provider_event_id).
 *      - On success, proceed to process.
 *      - On unique-constraint violation, this is a duplicate — ack 200, skip.
 *   3. Process the event (update invoice, fire domain event, etc.).
 *   4. UPDATE webhook_events SET processed_at = now().
 *
 * Note: we deliberately keep this table separate from the invoices table.
 * One webhook delivery may reference an invoice we've already finalised
 * (in which case we still want to record we received it but skip the
 * state mutation), and some events have no invoice at all.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();

            // Which provider's webhook this is. Strings so new rails are
            // additive. Examples: 'stripe', 'stripe_connect', 'transak',
            // 'moonpay', 'nowpayments', 'tap', 'checkout_com'.
            $table->string('rail', 32);

            // The provider's id for this specific webhook event. For Stripe:
            // event.id (evt_xxx). For Transak: eventID. For NOWPayments:
            // payment_id at the moment of delivery. Always exists in
            // well-designed webhooks; if a rail doesn't send one, we
            // synthesise from headers + body hash (rail driver's call).
            $table->string('provider_event_id');

            // Type of event for filtering ('checkout.session.completed',
            // 'charge.refunded', 'ORDER_PROCESSING', etc.). Optional —
            // not every rail provides a useful classifier and we don't
            // dispatch on it inside this table.
            $table->string('event_type', 128)->nullable();

            // When we received the request.
            $table->timestamp('received_at')->useCurrent();
            // When we finished processing it. NULL while in-flight; used
            // to find stuck/unprocessed events for the DLQ task in 4b.
            $table->timestamp('processed_at')->nullable();

            // The full raw payload. JSON column is fine because we always
            // store the parsed-and-re-serialized form (after signature
            // verification has already run on the raw bytes).
            $table->json('payload')->nullable();

            // The dedup anchor. (rail, provider_event_id) is the unique key —
            // duplicate deliveries hit this and we know to skip.
            $table->unique(['rail', 'provider_event_id']);

            // Helps the "find stuck events" query — eventually used by
            // the dead-letter-queue scan (Phase 4b).
            $table->index(['rail', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
