<?php

namespace Modules\Wallet\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Models\WebhookEvent;
use Modules\Wallet\Services\Payment\Rails\NowPaymentsWebhookHandler;
use Throwable;

/**
 * Receives NowPayments IPN events at /api/v1/wallet/webhooks/nowpayments.
 *
 * NO authentication: NowPayments doesn't have credentials. Every IPN is
 * signed with the IPN secret (services.nowpayments.ipn_secret), verified
 * inside NowPaymentsWebhookHandler before any processing.
 *
 * Three-step pipeline:
 *   1. Verify x-nowpayments-sig header (HMAC-SHA512 over sorted JSON).
 *      Bad sig → 400, NowPayments will NOT retry an unauthorized payload.
 *   2. Dedupe via the webhook_events table — INSERT on (rail, provider_event_id).
 *      Synthetic event id: "{payment_id}:{payment_status}". A constraint
 *      violation means we've already processed this transition; ack 200.
 *   3. Hand the payload to NowPaymentsWebhookHandler, mark webhook_events
 *      row processed on success.
 *
 * Status codes:
 *   200 — success (or known duplicate)
 *   400 — missing or invalid signature (NowPayments will not retry)
 *   500 — internal error during processing (NowPayments retries per the
 *         dashboard "recurring notifications" config)
 */
class NowPaymentsWebhookController extends Controller
{
    public function __construct(
        protected NowPaymentsWebhookHandler $handler,
    ) {
    }

    public function handle(Request $request)
    {
        // ── Step 1: signature ──
        $signature = $request->header('x-nowpayments-sig');
        if ($signature === null || $signature === '') {
            return response()->json(
                ['status' => false, 'message' => 'Missing x-nowpayments-sig header'],
                400,
            );
        }

        // RAW body — the signature is computed over the canonical re-encoded
        // form, but Laravel's $request->all() may re-format and break parsing.
        $rawBody = $request->getContent();

        $payload = $this->handler->verifyAndParse($rawBody, $signature);
        if ($payload === null) {
            Log::warning('NowPayments webhook signature verification failed', [
                'sig_len'  => strlen($signature),
                'body_len' => strlen($rawBody),
            ]);
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 400);
        }

        // Synthetic event id — NowPayments doesn't ship a stable event_id
        // like Stripe does, but (payment_id, payment_status) is unique per
        // state transition and stable across retried deliveries of the
        // same transition. Catches both genuine duplicates and NP's
        // recurring-notification retries.
        $eventId = sprintf(
            '%s:%s',
            $payload['payment_id']     ?? 'unknown',
            $payload['payment_status'] ?? 'unknown',
        );
        $eventType = 'payment.' . ($payload['payment_status'] ?? 'unknown');

        // ── Step 2: dedup ──
        try {
            $webhookEvent = WebhookEvent::create([
                'rail'              => PaymentRail::NowPayments->value,
                'provider_event_id' => $eventId,
                'event_type'        => $eventType,
                'received_at'       => now(),
                'payload'           => $payload,
            ]);
        } catch (QueryException $e) {
            // SQLSTATE 23000 — unique constraint on (rail, provider_event_id).
            // Means we've already seen this transition; ack 200 with no work.
            if ($e->getCode() === '23000') {
                return response()->json(['status' => true, 'duplicate' => true], 200);
            }
            throw $e; // unexpected DB error — bubble up to 500 so NP retries
        }

        // ── Step 3: process ──
        try {
            $this->handler->handle($payload);
            $webhookEvent->markProcessed();
            return response()->json(['status' => true], 200);
        } catch (Throwable $e) {
            Log::error('NowPayments webhook processing failed', [
                'event_id' => $eventId,
                'type'     => $eventType,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => false, 'message' => 'Internal error'], 500);
        }
    }
}
