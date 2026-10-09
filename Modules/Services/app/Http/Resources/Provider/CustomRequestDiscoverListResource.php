<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Http\Resources\User\CustomRequestCategoryResource;

class CustomRequestDiscoverListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'deadline_date' => $this->deadline_date?->format('Y-m-d'),
            'budget_min' => $this->budget_min !== null ? (float) $this->budget_min : null,
            'budget_max' => $this->budget_max !== null ? (float) $this->budget_max : null,
            'quotes_count' => $this->whenCounted('quotes'),
            'has_applied' => (bool) ($this->has_applied ?? false),
            'category' => CustomRequestCategoryResource::make($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
