<?php

namespace Modules\Wallet\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a Wallet for user-facing endpoints.
 *
 * The user_id is intentionally NOT included — clients never need to see it
 * (the wallet they're viewing is always their own), and exposing it would
 * leak the database PK to no benefit.
 */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'available_balance' => $this->available_balance,
            'pending_balance'   => $this->pending_balance,
            'locked_balance'    => $this->locked_balance,
            'currency'          => $this->currency,
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
