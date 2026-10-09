<?php

namespace Modules\Services\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoostPlanVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
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
