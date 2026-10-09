<?php

namespace Modules\Wallet\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Models\WebhookEvent;
use Modules\Wallet\Services\Payment\Rails\StripeWebhookHandler;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

/**
 * Receives Stripe webhook events.
 *
 * NO authentication: Stripe doesn't have credentials to log in. Instead,
 * every webhook is signed with the webhook secret (services.stripe.webhook_secret),
 * verified inside the StripeWebhookHandler before any processing.
 *
 * Three-step pipeline:
 *   1. Verify Stripe-Signature header (rejects bad-sig with 400; Stripe
 *      should NOT retry an unauthorized payload).
 *   2. Dedupe via the webhook_events table — INSERT on (rail, provider_event_id);
 *      if the unique constraint trips, this is a duplicate delivery — ack 200,
 *      skip processing.
 *   3. Hand the event to StripeWebhookHandler, then mark the webhook_events
 *      row processed.
 *
 * Status codes:
 *   200 — success (or known duplicate)
 *   400 — missing or invalid signature (Stripe will not retry)
 *   500 — internal error during processing (Stripe will retry)
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeWebhookHandler $handler,
    ) {
    }

    public function handle(Request $request)
    {
        // ── Step 1: signature ──
        $signature = $request->header('Stripe-Signature');
        if ($signature === null || $signature === '') {
            return response()->json(
                ['status' => false, 'message' => 'Missing Stripe-Signature header'],
                400,
            );
        }

        // Use the RAW body — re-encoding via $request->all() alters bytes
        // and breaks signature verification.
        $rawBody = $request->getContent();

        try {
            $event = $this->handler->verifyAndParse($rawBody, $signature);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 400);
        } catch (Throwable $e) {
            // Malformed payload — bad request from Stripe's side or someone
            // testing endpoints with garbage. 400 so they don't retry.
            Log::warning('Stripe webhook payload malformed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Malformed payload'], 400);
        }

        // ── Step 2: dedup ──
        // Try to record the event id. On unique-constraint collision this
        // is a duplicate Stripe delivery; ack 200 and skip processing.
        try {
            $webhookEvent = WebhookEvent::create([
                'rail'              => PaymentRail::Stripe->value,
                'provider_event_id' => $event->id,
                'event_type'        => $event->type,
                'received_at'       => now(),
                'payload'           => $event->toArray(),
            ]);
        } catch (QueryException $e) {
            // SQLSTATE 23000 = integrity constraint violation (the unique
            // index on (rail, provider_event_id) we set up in the migration).
            if ($e->getCode() === '23000') {
                return response()->json(['status' => true, 'duplicate' => true], 200);
            }
            throw $e; // unexpected DB error — let it 500 so Stripe retries.
        }

        // ── Step 3: process ──
        try {
            $this->handler->handle($event);
            $webhookEvent->markProcessed();
            return response()->json(['status' => true], 200);
        } catch (Throwable $e) {
            // Domain-level processing failure. Leave webhook_events.processed_at
            // null so a future DLQ scan can find it; return 500 so Stripe
            // retries on its schedule.
            Log::error('Stripe webhook processing failed', [
                'event_id' => $event->id,
                'type'     => $event->type,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => false, 'message' => 'Internal error'], 500);
        }
    }
}
