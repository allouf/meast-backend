<?php

namespace Modules\Wallet\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of an Invoice for user-facing endpoints.
 *
 * Replaces the previous `WalletTopupIntentResource`. The output JSON
 * keeps the SAME field names (`stripe_session_id`, `stripe_payment_intent_id`)
 * for backwards compatibility with the existing frontend, even though
 * the underlying schema now stores them as `partner_reference_id` and
 * `metadata->stripe_payment_intent_id` respectively.
 *
 * This shim lets the frontend keep working unchanged while we evolve the
 * backend. When the frontend team is ready to migrate, we add the new
 * generic field names alongside (e.g. `partner_reference_id`) and
 * eventually drop the Stripe-specific ones in a follow-up.
 *
 * Note: only Stripe rail invoices have meaningful values for the
 * stripe_*_id legacy fields. Non-Stripe rails (StripeConnect uses
 * Stripe under the hood; future NowPayments etc. don't) will surface
 * `null` for those fields, which is the same behaviour as before for
 * any not-yet-completed top-up.
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metadata = (array) ($this->metadata ?? []);

        return [
            'id'                       => $this->id,
            'amount'                   => $this->amount,
            'currency'                 => $this->currency,
            'status'                   => $this->status->value,

            // Backwards-compatible field names. These keep the frontend
            // contract stable during the rename. Source of truth has
            // moved (see comments) but the JSON shape is unchanged.
            'stripe_session_id'        => $this->partner_reference_id,                  // was: stripe_session_id
            'stripe_payment_intent_id' => $metadata['stripe_payment_intent_id'] ?? null, // moved into metadata JSON

            'failure_reason'           => $this->failure_reason,
            'completed_at'             => $this->completed_at?->toIso8601String(),
            'created_at'               => $this->created_at?->toIso8601String(),
        ];
    }
}
