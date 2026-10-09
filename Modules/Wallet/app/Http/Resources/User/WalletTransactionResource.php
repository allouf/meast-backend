<?php

namespace Modules\Wallet\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a WalletTransaction for user-facing endpoints.
 *
 * The internal wallet_id is omitted (the user knows it's their wallet —
 * any value would be redundant). The polymorphic reference is exposed
 * because the client may want to deep-link back to the source order
 * or payment.
 */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type->value,
            'amount'         => $this->amount,
            'balance_before' => $this->balance_before,
            'balance_after'  => $this->balance_after,
            'reference'      => $this->reference_type !== null
                ? ['type' => $this->reference_type, 'id' => $this->reference_id]
                : null,
            'metadata'       => $this->metadata,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
