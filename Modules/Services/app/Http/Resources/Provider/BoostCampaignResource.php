<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoostCampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_offer_id' => $this->service_offer_id,
            'boost_plan_id' => $this->boost_plan_id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'terminated_at' => $this->terminated_at?->toIso8601String(),
            'targeting_scope' => $this->targeting_scope ?? [],
            'amount' => $this->amount,
            'currency' => $this->currency,
            'is_active_now' => $this->resource->isActive(),
            'service_offer' => $this->whenLoaded('offer', function () {
                return [
                    'id' => $this->offer->id,
                    'title' => $this->offer->title,
                    'status' => $this->offer->status instanceof \BackedEnum ? $this->offer->status->value : $this->offer->status,
                ];
            }),
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan->id,
                    'name' => $this->plan->name,
                    'duration_days' => $this->plan->duration_days,
                ];
            }),
        ];
    }
}
