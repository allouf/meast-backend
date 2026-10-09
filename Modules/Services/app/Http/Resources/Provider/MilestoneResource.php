<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_provider_id' => $this->service_provider_id,
            'service_offer_order_id' => $this->service_offer_order_id,
            'title' => $this->title,
            'description' => $this->description,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'request_modification_days' => $this->request_modification_days,
            'duration_days' => $this->duration_days,
            'total_budget' => (float) $this->total_budget,
            'status' => $this->status?->value ?? $this->status,
            'activation_status' => (bool) $this->activation_status,
            'sort_order' => $this->sort_order,
            'summary' => [
                'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
                'timeline_days' => $this->duration_days,
                'total_budget' => (float) $this->total_budget,
                'request_modification_days' => $this->request_modification_days,
            ],
            'services' => MilestoneServiceLineResource::collection($this->whenLoaded('milestoneServices')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
