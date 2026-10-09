<?php

namespace Modules\Wallet\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Wallet\Models\Invoice;

/**
 * Fired when an Invoice transitions to status=Failed.
 *
 * "Failed" specifically means the rail reported a payment-attempt error
 * (card declined, insufficient funds, etc.). Distinct from:
 *   - Cancelled (user-initiated)
 *   - Expired (timeout, no payment attempt completed)
 */
class PaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $reason,
    ) {
    }
}
