<?php

namespace Modules\Services\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Services\Enums\MilestoneOrderPhaseStatus;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Models\ServiceOfferOrderPhase;
use Modules\Wallet\Enums\EscrowStatus;
use Modules\Wallet\Events\PaymentSucceeded;

/**
 * Reacts to PaymentSucceeded events whose Invoice's referenceable is a
 * ServiceOfferOrderPhase (i.e. a milestone phase payment).
 *
 * Replaces the placeholder fake-escrow path that the milestone work shipped
 * with on origin/development (MilestoneOrderPhaseService::payPhase used to
 * create an escrow row with gateway='manual' and a synthetic transaction_id).
 * Now the phase escrow carries the real rail + partner_reference_id from
 * the Invoice — same pattern the single-offer flow already uses via
 * MarkServiceOrderPaidOnInvoiceCompletion.
 *
 * Filtering: instanceof ServiceOfferOrderPhase. Single-offer orders, wallet
 * top-ups, ad packages, and other invoice kinds pass through as no-ops.
 *
 * Idempotency:
 *   - If phase is no longer PendingPayment → skip.
 *   - If phase already has a Paid escrow → skip.
 * Either guard alone would catch a duplicate; both together cover the
 * webhook-replay-after-rerun edge case.
 *
 * Transactional: escrow insert + parent order status bump share one DB
 * transaction. If either fails both roll back, leaving the invoice
 * Completed and the phase recoverable (re-fire the event or admin replay).
 */
class MarkServiceOrderPhasePaidOnInvoiceCompletion
{
    public function handle(PaymentSucceeded $event): void
    {
        $invoice = $event->invoice;
        $phase   = $invoice->referenceable;

        if (! $phase instanceof ServiceOfferOrderPhase) {
            return; // not our invoice
        }

        if ($phase->status !== MilestoneOrderPhaseStatus::PendingPayment) {
            Log::info('Phase payment-succeeded listener: phase not in PendingPayment — skipping', [
                'phase_id'       => $phase->id,
                'current_status' => $phase->status->value,
                'invoice_id'     => $invoice->id,
            ]);
            return;
        }

        if ($phase->isFunded()) {
            Log::info('Phase payment-succeeded listener: phase already funded — skipping', [
                'phase_id'   => $phase->id,
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        DB::transaction(function () use ($phase, $invoice) {
            // morphMany on $phase->escrows() auto-fills escrowable_type/_id.
            $phase->escrows()->create([
                'invoice_id'     => $invoice->id,
                'gateway'        => $invoice->rail,
                'transaction_id' => $invoice->partner_reference_id,
                'amount'         => $invoice->amount,
                'currency'       => $invoice->currency,
                'status'         => EscrowStatus::Paid,
                'paid_at'        => now(),
            ]);

            // Bump the parent order to InProgress on first phase payment.
            // Subsequent phases land while the order is already InProgress —
            // their escrow creation alone is the signal.
            $order = $phase->order;
            if ($order && $order->status === ServiceOfferOrderStatus::PendingPayment) {
                $order->update(['status' => ServiceOfferOrderStatus::InProgress]);
            }
        });
    }
}
