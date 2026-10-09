<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\OrderableType;

class MilestonePackageListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryCategory = null;
        if ($this->relationLoaded('milestoneServices') && $this->milestoneServices->isNotEmpty()) {
            $line = $this->milestoneServices->first();
            if ($line->relationLoaded('serviceCategory') && $line->serviceCategory) {
                $primaryCategory = $line->serviceCategory;
            }
        }

        return [
            'id' => $this->id,
            'orderable_type' => OrderableType::MilestoneTemplate->value,
            'orderable_id' => $this->id,
            'title' => $this->title,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'delivery_time' => [
                'value' => (int) $this->duration_days,
                'unit' => 'days',
            ],
            'description' => (string) ($this->description ?? ''),
            'price_from' => (float) $this->total_budget,
            'duration_days' => (int) $this->duration_days,
            'request_modification_days' => $this->request_modification_days,
            'services_count' => $this->when(
                isset($this->milestone_services_count),
                fn () => $this->milestone_services_count
            ),
            'service_category' => $primaryCategory ? [
                'id' => $primaryCategory->id,
                'name' => (string) ($primaryCategory->getTranslation('name', app()->getLocale())
                    ?? $primaryCategory->getTranslation('name', config('app.fallback_locale'))
                    ?? ''),
                'slug' => $primaryCategory->slug,
            ] : null,
            'service_provider' => $this->whenLoaded('serviceProvider', fn () => [
                'id' => $this->serviceProvider->id,
                'display_name' => (string) ($this->serviceProvider->display_name ?? ''),
                'region' => (string) ($this->serviceProvider->region ?? ''),
                'country_code' => (string) ($this->serviceProvider->country_code ?? ''),
            ]),
            'is_sponsored' => false,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
