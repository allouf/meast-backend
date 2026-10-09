<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'discount_type'  => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'min_cart_amount'     => (float) ($this->min_cart_amount ?? 0),
            'expires_at'     => $this->expires_at?->format('Y-m-d'),
            'max_usage_count'     => $this->max_usage_count,
            'max_usage_per_user'  => $this->max_usage_per_user,
            'is_expired'     => $this->isExpired(),
            'scope'          => $this->scope,
            'remaining_usage'=> $this->remainingUsage(),
        ];

    }
}
