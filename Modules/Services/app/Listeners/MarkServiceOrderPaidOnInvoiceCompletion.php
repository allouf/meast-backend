<?php

namespace Modules\Services\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Models\ServiceOfferOrder;
use Modules\Wallet\Enums\EscrowStatus;
use Modules\Wallet\Events\PaymentSucceeded;

/**
 * Reacts to PaymentSucceeded events whose Invoice's referenceable is a
 * ServiceOfferOrder.
 *
 * Replaces the old fake-escrow path (ServiceOfferOrderService::createPaidEscrow,
 * which fabricated a row with gateway='manual' + uniqid). Now the escrow
 * carries the real rail + partner reference id from the Invoice.
 *
 * Filtering: instanceof ServiceOfferOrder. Wallet top-ups, ad packages, and
 * other unrelated invoice kinds pass through this listener as no-ops.
 *
 * Idempotency: if the order is no longer in PendingPayment, skip. This
 * complements the webhook_events dedup at the rail layer — if both happen
 * to fire (replay through both layers), the second one finds the order
 * already Pending and bails out cleanly.
 *
 * Transactional: the escrow insert + status flip share a single DB transaction.
 * If either fails, both roll back and the invoice stays Completed — leaving
 * the order in a recoverable state (it can be re-driven by re-firing the
 * event or by an admin re-running the listener).
 */
class MarkServiceOrderPaidOnInvoiceCompletion
{
    public function handle(PaymentSucceeded $event): void
    {
        $invoice = $event->invoice;
        $order   = $invoice->referenceable;

        if (! $order instanceof ServiceOfferOrder) {
            return; // not our invoice
        }

        if ($order->status !== ServiceOfferOrderStatus::PendingPayment) {
            Log::info('ServiceOrder payment-succeeded listener: order not in PendingPayment — skipping', [
                'order_id'       => $order->id,
                'current_status' => $order->status->value,
                'invoice_id'     => $invoice->id,
            ]);
            return;
        }

        DB::transaction(function () use ($order, $invoice) {
            // morphMany on $order->escrows() auto-fills escrowable_type/_id.
            $order->escrows()->create([
                'invoice_id'     => $invoice->id,
                'gateway'        => $invoice->rail,
                'transaction_id' => $invoice->partner_reference_id,
                'amount'         => $invoice->amount,
                'currency'       => $invoice->currency,
                'status'         => EscrowStatus::Paid,
                'paid_at'        => now(),
            ]);

            $order->update(['status' => ServiceOfferOrderStatus::Pending]);
        });
    }
}
