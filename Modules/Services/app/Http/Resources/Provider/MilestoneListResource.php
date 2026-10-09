<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'duration_days' => $this->duration_days,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'request_modification_days' => $this->request_modification_days,
            'total_budget' => (float) $this->total_budget,
            'activation_status' => (bool) $this->activation_status,
            'services_count' => $this->when(isset($this->milestone_services_count), fn () => $this->milestone_services_count),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
