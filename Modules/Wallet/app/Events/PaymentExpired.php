<?php

namespace Modules\Wallet\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Wallet\Models\Invoice;

/**
 * Fired when a pending Invoice exceeds its expires_at without being completed.
 *
 * Triggered from the ExpireStalePendingInvoicesCommand cron job (Step 7
 * of the Track 4 plan). Listeners typically use this to release any
 * domain-side hold the buyer had on the buyable thing (e.g. release a
 * reserved service-order slot back to the marketplace).
 */
class PaymentExpired
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Invoice $invoice)
    {
    }
}
