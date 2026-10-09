<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOfferOrderExtensionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requested_days' => $this->requested_days,

            'reason' => [
                'id' => $this->reason?->id,
                'name' => $this->reason?->name,
            ],

            'description' => $this->description,

            'status' => $this->status?->value ?? $this->status,

            'requested_at' => $this->created_at?->toDateTimeString(),
            'responded_at' => $this->responded_at?->toDateTimeString(),
        ];
    }
}
