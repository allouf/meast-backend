<?php

namespace Modules\Services\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoostPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_price' => $this->base_price,
            'currency' => $this->currency,
            'duration_days' => $this->duration_days,
            'targeting' => $this->targeting ?? [],
            'is_active' => $this->is_active,
            'variants' => BoostPlanVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
