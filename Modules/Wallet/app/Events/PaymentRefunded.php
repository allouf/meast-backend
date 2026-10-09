<?php

namespace Modules\Wallet\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;

/**
 * Fired when a refund is applied to an Invoice (full or partial).
 *
 * Carries both the parent Invoice and the specific InvoiceRefund row so
 * listeners can decide based on amount whether the refund was full
 * ($refund->amount === $invoice->amount) or partial.
 */
class PaymentRefunded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly InvoiceRefund $refund,
    ) {
    }
}
