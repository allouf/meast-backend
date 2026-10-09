<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\CustomRequestStatus;
use Modules\Services\Enums\QuoteStatus;
use Modules\Services\Http\Resources\User\CustomRequestAttachmentResource;
use Modules\Services\Http\Resources\User\CustomRequestCategoryResource;
use Modules\Services\Utilities\ServiceTimeHelper;

class CustomRequestDiscoverDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $providerQuotes = $this->relationLoaded('quotes') ? $this->quotes : collect();
        $myQuote = $providerQuotes->first(fn($q) => $q->status !== QuoteStatus::Canceled)
            ?? $providerQuotes->first();
        $hasApplied = $providerQuotes->contains(fn($q) => $q->status !== QuoteStatus::Canceled);

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
            'has_applied' => $hasApplied,
            'time_remaining' => match ($this->status?->value ?? $this->status) {

                CustomRequestStatus::Open->value =>
                ServiceTimeHelper::fromCreatedAt(
                    $this->created_at,
                    config('services-marketplace.rfq_discovery_duration_days', 3)
                ),

                CustomRequestStatus::InProgress->value =>
                ServiceTimeHelper::countdown(
                    $this->deadline_date
                ),

                default => null,
            },
            'category' => CustomRequestCategoryResource::make($this->whenLoaded('category')),
            'attachments' => CustomRequestAttachmentResource::collection($this->whenLoaded('attachments')),
            'my_quote' => $myQuote ? new ProviderCustomRequestQuoteSummaryResource($myQuote) : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
