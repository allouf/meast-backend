<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoostPlanVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'boost_plan_id' => $this->boost_plan_id,
            'country_code' => $this->country_code,
            'price' => $this->price,
            'currency' => $this->currency,
            'is_active' => $this->is_active
        ];
    }
}
