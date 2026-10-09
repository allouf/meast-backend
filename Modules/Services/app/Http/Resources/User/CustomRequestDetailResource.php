<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\CustomRequestStatus;
use Modules\Services\Utilities\ServiceTimeHelper;

class CustomRequestDetailResource extends JsonResource
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
            'deadline_date' => $this->deadline_date?->toDateTimeString(),
            'budget_min' => $this->budget_min !== null ? (float) $this->budget_min : null,
            'budget_max' => $this->budget_max !== null ? (float) $this->budget_max : null,

            'time_remaining' => match ($this->status) {

                CustomRequestStatus::Open =>
                ServiceTimeHelper::fromCreatedAt(
                    $this->created_at,
                    config('services-marketplace.rfq_open_duration_days', 14)
                ),

                CustomRequestStatus::InProgress =>
                ServiceTimeHelper::countdown(
                    $this->deadline_at
                ),

                default => null,
            },

            'category' => CustomRequestCategoryResource::make($this->whenLoaded('category')),
            'attachments' => CustomRequestAttachmentResource::collection($this->whenLoaded('attachments')),
            'quotes' => CustomRequestQuoteResource::collection($this->whenLoaded('quotes')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
