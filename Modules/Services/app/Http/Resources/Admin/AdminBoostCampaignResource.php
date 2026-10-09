<?php

namespace Modules\Services\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBoostCampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_offer_id' => $this->service_offer_id,
            'provider_id' => $this->provider_id,
            'boost_plan_id' => $this->boost_plan_id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'terminated_at' => $this->terminated_at?->toIso8601String(),
            'targeting_scope' => $this->targeting_scope ?? [],
            'amount' => $this->amount,
            'currency' => $this->currency,
            'service_offer' => $this->whenLoaded('offer', fn () => [
                'id' => $this->offer->id,
                'title' => $this->offer->title,
                'status' => $this->offer->status instanceof \BackedEnum ? $this->offer->status->value : $this->offer->status,
                'is_active' => (bool) $this->offer->is_active,
            ]),
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider->id,
                'display_name' => (string) ($this->provider->display_name ?? ''),
                'user_id' => $this->provider->user_id,
            ]),
            'plan' => $this->when(
                $this->relationLoaded('plan') && $this->plan,
                fn () => new BoostPlanResource($this->plan)
            ),
            'logs' => $this->whenLoaded('logs', fn () => BoostCampaignLogResource::collection($this->logs)),
        ];
    }
}
