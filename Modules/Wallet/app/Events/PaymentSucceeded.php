<?php

namespace Modules\Wallet\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Wallet\Models\Invoice;

/**
 * Fired when an Invoice transitions to status=Completed.
 *
 * Fired from inside Invoice::markCompleted(), within the same DB
 * transaction as the status update + audit log row. Listeners filter
 * on `$event->invoice->referenceable instanceof TheirModel` to react
 * only to their own invoices.
 *
 * Standard listener pattern (see docs/payment-integration-contract.md):
 *
 *   public function handle(PaymentSucceeded $event): void {
 *       $ref = $event->invoice->referenceable;
 *       if (! $ref instanceof MyDomainModel) return;
 *       $ref->update(['status' => 'paid']);
 *   }
 */
class PaymentSucceeded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Invoice $invoice)
    {
    }
}
