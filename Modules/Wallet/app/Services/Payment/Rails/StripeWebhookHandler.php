<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\InvoiceStatus;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Enums\RefundReason;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;
use Modules\Wallet\Models\SellerStripeAccount;
use Modules\Wallet\Services\WalletService;
use Stripe\Account as StripeAccount;
use Stripe\Charge as StripeCharge;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Event as StripeEvent;
use Stripe\Refund as StripeRefund;
use Stripe\Webhook as StripeWebhook;

/**
 * Processes incoming Stripe webhook events for the StripeRail.
 *
 * Replaces the inbound half of StripeTopupService. Outbound (initiate /
 * refund) lives in StripeRail; this handler is INBOUND-only.
 *
 * The webhook controller calls verifyAndParse() to authenticate and
 * decode the raw payload, then handle() to dispatch on event type.
 * Webhook deduplication (via the webhook_events table) is the
 * controller's responsibility — this handler assumes it's been called
 * exactly once per real event.
 *
 * Event handling:
 *   - checkout.session.completed  → invoice->markCompleted() + credit wallet
 *   - checkout.session.expired    → invoice->markCancelled('Checkout session expired')
 *   - checkout.session.async_payment_failed → invoice->markFailed('Async payment failed')
 *   - charge.refunded              → create InvoiceRefund(s), credit wallet, invoice->markRefunded()
 *
 * Other event types are ignored (Stripe sends many we don't care about).
 */
class StripeWebhookHandler
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly string $webhookSecret,
        private readonly StripeOnboardingService $onboarding,
    ) {
    }

    /**
     * Verify the Stripe-Signature header against the raw body and return
     * the parsed Event. Throws on failure (caller decides how to respond).
     *
     * @throws \Stripe\Exception\SignatureVerificationException
     * @throws \UnexpectedValueException
     */
    public function verifyAndParse(string $rawBody, string $signatureHeader): StripeEvent
    {
        return StripeWebhook::constructEvent($rawBody, $signatureHeader, $this->webhookSecret);
    }

    /**
     * Dispatch a verified event to the right handler. Returns the event id
     * for logging / dedup purposes.
     */
    public function handle(StripeEvent $event): void
    {
        match ($event->type) {
            'checkout.session.completed'              => $this->onCheckoutSessionCompleted($event),
            'checkout.session.expired'                => $this->onCheckoutSessionExpired($event),
            'checkout.session.async_payment_failed'   => $this->onCheckoutSessionFailed($event),
            // Refunds: prefer refund.created (current Stripe API surface — the
            // payload IS the refund object). charge.refunded is kept as a
            // fallback for older API versions; the partner_refund_id dedup
            // makes processing both events safe.
            'refund.created'                          => $this->onRefundCreated($event),
            'charge.refunded'                         => $this->onChargeRefunded($event),
            // Connect events. Stripe sends these when a seller's Connect
            // account state changes (KYB submitted, charges enabled,
            // requirements pending, etc.). We resync our local row.
            'account.updated'                         => $this->onAccountUpdated($event),
            default                                   => null, // ignore — many event types are noise
        };
    }

    /* ─────────────────────────── handlers ─────────────────────────── */

    private function onCheckoutSessionCompleted(StripeEvent $event): void
    {
        /** @var StripeCheckoutSession $session */
        $session = $event->data->object;

        $invoice = $this->findInvoiceBySessionId($session->id);
        if ($invoice === null) {
            return; // event for a session we didn't create — log + skip
        }

        // Already processed? markCompleted() is itself idempotent but we
        // can short-circuit before doing the wallet credit.
        if ($invoice->isCompleted()) {
            return;
        }

        // For card payments Stripe marks payment_status='paid' synchronously
        // with this event. For async methods (bank debit etc.) it arrives
        // separately via async_payment_succeeded — we skip the credit now
        // and pick it up later if/when we implement that handler.
        if ($session->payment_status !== 'paid') {
            return;
        }

        // Credit the buyer's wallet first (so if it fails we don't have a
        // Completed invoice with no corresponding credit). Then transition
        // the invoice via the state-machine method, which writes the audit
        // log + fires PaymentSucceeded.
        DB::transaction(function () use ($invoice, $session) {
            $this->walletService->credit(
                userId:    $invoice->user_id,
                amount:    (string) $invoice->amount,
                reference: ['type' => Invoice::class, 'id' => $invoice->id],
                metadata:  [
                    'gateway'                  => 'stripe',
                    'partner_reference_id'     => $session->id,
                    'stripe_payment_intent_id' => $session->payment_intent,
                ],
            );

            $invoice->markCompleted(
                partnerReferenceId: $session->id,
                metadataPatch:      ['stripe_payment_intent_id' => $session->payment_intent],
                actorType:          'webhook',
            );
        });
    }

    private function onCheckoutSessionExpired(StripeEvent $event): void
    {
        /** @var StripeCheckoutSession $session */
        $session = $event->data->object;
        $invoice = $this->findInvoiceBySessionId($session->id);
        if ($invoice === null || ! $invoice->isPending()) {
            return;
        }
        $invoice->markCancelled(actorType: 'webhook', reason: 'Checkout session expired');
    }

    private function onCheckoutSessionFailed(StripeEvent $event): void
    {
        /** @var StripeCheckoutSession $session */
        $session = $event->data->object;
        $invoice = $this->findInvoiceBySessionId($session->id);
        if ($invoice === null || ! $invoice->isPending()) {
            return;
        }
        $invoice->markFailed('Async payment failed', actorType: 'webhook');
    }

    /**
     * Stripe fires refund.created when a refund is initiated against a
     * charge. The event payload's data.object IS the Refund (with `charge`
     * and `payment_intent` as IDs), so we get everything we need without
     * an extra API round-trip.
     *
     * This is the primary refund path. We process exactly one refund per
     * event and rely on the partner_refund_id unique index for idempotency
     * — if Stripe ever redelivers, the second insert is a no-op.
     */
    private function onRefundCreated(StripeEvent $event): void
    {
        /** @var StripeRefund $stripeRefund */
        $stripeRefund = $event->data->object;

        // Only act on succeeded refunds. Card refunds settle synchronously,
        // so refund.created arrives with status='succeeded'. Bank-debit
        // refunds may arrive 'pending' and finalize via refund.updated —
        // we don't handle that path yet (Stripe Checkout is card-only).
        if ($stripeRefund->status !== 'succeeded') {
            return;
        }

        // Idempotency: skip refunds we've already recorded.
        if (InvoiceRefund::query()->where('partner_refund_id', $stripeRefund->id)->exists()) {
            return;
        }

        $invoice = Invoice::query()
            ->whereIn('rail', [PaymentRail::Stripe->value, PaymentRail::StripeConnect->value])
            ->where('metadata->stripe_payment_intent_id', $stripeRefund->payment_intent)
            ->first();

        if ($invoice === null) {
            Log::warning('Stripe refund.created for unknown payment_intent', [
                'refund_id'         => $stripeRefund->id,
                'payment_intent_id' => $stripeRefund->payment_intent,
            ]);
            return;
        }

        $amountMajor = bcdiv((string) $stripeRefund->amount, '100', 8);

        DB::transaction(function () use ($invoice, $stripeRefund, $amountMajor) {
            $tx = $this->walletService->refund(
                userId:    $invoice->user_id,
                amount:    $amountMajor,
                reference: ['type' => InvoiceRefund::class, 'id' => 0], // patched below
                metadata:  [
                    'gateway'                  => 'stripe',
                    'stripe_refund_id'         => $stripeRefund->id,
                    'stripe_charge_id'         => $stripeRefund->charge,
                    'stripe_payment_intent_id' => $stripeRefund->payment_intent,
                    'reason'                   => $stripeRefund->reason ?? null,
                    'invoice_id'               => $invoice->id,
                ],
            );

            $refund = InvoiceRefund::create([
                'invoice_id'             => $invoice->id,
                'wallet_id'              => $invoice->wallet_id,
                'amount'                 => $amountMajor,
                'currency'               => $invoice->currency,
                'partner_refund_id'      => $stripeRefund->id,
                'reason'                 => RefundReason::fromRailString($stripeRefund->reason ?? null)->value,
                'metadata'               => ['stripe_charge_id' => $stripeRefund->charge],
                'wallet_transaction_id'  => $tx->id,
            ]);

            $tx->update(['reference_id' => $refund->id]);

            $invoice->markRefunded($refund, actorType: 'webhook');
        });
    }

    /**
     * Legacy/fallback path: Stripe historically fired charge.refunded with
     * the full refunds list embedded in `charge.refunds.data[]`. Newer API
     * versions no longer auto-expand that list, so this handler can be a
     * no-op in practice — refund.created carries the work.
     *
     * Kept for defence-in-depth: if a deployment runs against an older API
     * version where `charge.refunds.data` IS populated, this still works,
     * and the partner_refund_id dedup prevents double-processing.
     */
    private function onChargeRefunded(StripeEvent $event): void
    {
        /** @var StripeCharge $charge */
        $charge = $event->data->object;

        // Find the originating Invoice via the metadata JSON. The
        // payment_intent is stored there for cross-rail consistency.
        $invoice = Invoice::query()
            ->whereIn('rail', [PaymentRail::Stripe->value, PaymentRail::StripeConnect->value])
            ->where('metadata->stripe_payment_intent_id', $charge->payment_intent)
            ->first();

        if ($invoice === null) {
            Log::warning('Stripe charge.refunded for unknown payment_intent', [
                'charge_id'         => $charge->id,
                'payment_intent_id' => $charge->payment_intent,
            ]);
            return;
        }

        $refunds = $charge->refunds->data ?? [];

        foreach ($refunds as $stripeRefund) {
            // Idempotency: skip refunds we've already recorded.
            if (InvoiceRefund::query()->where('partner_refund_id', $stripeRefund->id)->exists()) {
                continue;
            }

            // Stripe amounts are minor units; convert to major.
            $amountMajor = bcdiv((string) $stripeRefund->amount, '100', 8);

            DB::transaction(function () use ($invoice, $charge, $stripeRefund, $amountMajor) {
                // 1. Apply the refund to the buyer's platform credit. The
                //    WalletService::refund call returns the WalletTransaction
                //    row we'll back-link to from InvoiceRefund.
                $tx = $this->walletService->refund(
                    userId:    $invoice->user_id,
                    amount:    $amountMajor,
                    reference: ['type' => InvoiceRefund::class, 'id' => 0], // patched below
                    metadata:  [
                        'gateway'                  => 'stripe',
                        'stripe_refund_id'         => $stripeRefund->id,
                        'stripe_charge_id'         => $charge->id,
                        'stripe_payment_intent_id' => $charge->payment_intent,
                        'reason'                   => $stripeRefund->reason ?? null,
                        'invoice_id'               => $invoice->id,
                    ],
                );

                // 2. Create the InvoiceRefund row.
                $refund = InvoiceRefund::create([
                    'invoice_id'             => $invoice->id,
                    'wallet_id'              => $invoice->wallet_id,
                    'amount'                 => $amountMajor,
                    'currency'               => $invoice->currency,
                    'partner_refund_id'      => $stripeRefund->id,
                    'reason'                 => RefundReason::fromRailString($stripeRefund->reason ?? null)->value,
                    'metadata'               => ['stripe_charge_id' => $charge->id],
                    'wallet_transaction_id'  => $tx->id,
                ]);

                // 3. Back-fill the credit-ledger row's reference id.
                $tx->update(['reference_id' => $refund->id]);

                // 4. Transition the parent invoice (full refund only).
                //    Partial refunds keep the invoice at Completed; the
                //    state-machine method handles that distinction.
                $invoice->markRefunded($refund, actorType: 'webhook');
            });
        }
    }

    /**
     * Sync our local SellerStripeAccount row when Stripe reports an
     * account.updated event. Idempotent — applying the same payload
     * twice produces the same row state.
     *
     * If the account isn't one we've recorded, we ignore the event
     * (it's not for us). This can happen when Stripe sends events for
     * accounts that were created outside our flow.
     */
    private function onAccountUpdated(StripeEvent $event): void
    {
        /** @var StripeAccount $stripeAccount */
        $stripeAccount = $event->data->object;

        $local = SellerStripeAccount::query()
            ->where('stripe_account_id', $stripeAccount->id)
            ->first();

        if ($local === null) {
            Log::info('Stripe account.updated for unknown Connect account — ignoring', [
                'stripe_account_id' => $stripeAccount->id,
            ]);
            return;
        }

        $this->onboarding->applyStripePayload($local, $stripeAccount);
    }

    private function findInvoiceBySessionId(string $sessionId): ?Invoice
    {
        // Both Stripe rails (legacy top-up `stripe`, marketplace `stripe_connect`)
        // create Checkout Sessions, so look in either. partner_reference_id
        // is globally unique per session — no ambiguity in matching.
        $invoice = Invoice::query()
            ->whereIn('rail', [PaymentRail::Stripe->value, PaymentRail::StripeConnect->value])
            ->where('partner_reference_id', $sessionId)
            ->first();

        if ($invoice === null) {
            Log::warning('Stripe webhook references unknown checkout session', ['session_id' => $sessionId]);
        }

        return $invoice;
    }
}
