<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Http\Resources\Provider\MilestoneServiceLineResource;

class MilestonePackageDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderable_type' => OrderableType::MilestoneTemplate->value,
            'orderable_id' => $this->id,
            'service_provider_id' => $this->service_provider_id,
            'title' => $this->title,
            'description' => $this->description,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'request_modification_days' => $this->request_modification_days,
            'duration_days' => (int) $this->duration_days,
            'total_budget' => (float) $this->total_budget,
            'price_from' => (float) $this->total_budget,
            'services' => MilestoneServiceLineResource::collection($this->whenLoaded('milestoneServices')),
            'service_provider' => $this->whenLoaded('serviceProvider', fn () => [
                'id' => $this->serviceProvider->id,
                'display_name' => $this->serviceProvider->display_name,
                'region' => $this->serviceProvider->region,
                'country_code' => $this->serviceProvider->country_code,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
