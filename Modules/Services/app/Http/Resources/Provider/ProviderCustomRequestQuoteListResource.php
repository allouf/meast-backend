<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Http\Resources\User\CustomRequestCategoryResource;

class ProviderCustomRequestQuoteListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rfq = $this->relationLoaded('customRequest') ? $this->customRequest : null;

        return [
            'id' => $this->id,
            'proposed_budget' => (float) $this->proposed_budget,
            'estimated_delivery_date' => $this->estimated_delivery_date?->format('Y-m-d'),
            'cover_letter_or_message' => $this->cover_letter_or_message,
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'custom_request' => $rfq ? [
                'id' => $rfq->id,
                'reference_number' => $rfq->reference_number,
                'title' => $rfq->title,
                'delivery_model' => $rfq->delivery_model?->value ?? $rfq->delivery_model,
                'deadline_date' => $rfq->deadline_date?->format('Y-m-d'),
                'budget_min' => $rfq->budget_min !== null ? (float) $rfq->budget_min : null,
                'budget_max' => $rfq->budget_max !== null ? (float) $rfq->budget_max : null,
                'status' => $rfq->status?->value ?? $rfq->status,
                'category' => $rfq->relationLoaded('category')
                    ? CustomRequestCategoryResource::make($rfq->category)
                    : null,
            ] : null,
        ];
    }
}
