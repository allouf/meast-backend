<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCustomRequestQuoteSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposed_budget' => (float) $this->proposed_budget,
            'estimated_delivery_date' => $this->estimated_delivery_date?->format('Y-m-d'),
            'cover_letter_or_message' => $this->cover_letter_or_message,
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
