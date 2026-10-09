<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomRequestListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'title' => $this->title,
            'status' => $this->status?->value ?? $this->status,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'deadline_date' => $this->deadline_date?->format('Y-m-d'),
            'budget_min' => $this->budget_min !== null ? (float) $this->budget_min : null,
            'budget_max' => $this->budget_max !== null ? (float) $this->budget_max : null,
            'quotes_count' => $this->whenCounted('quotes'),
            'category' => CustomRequestCategoryResource::make($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
