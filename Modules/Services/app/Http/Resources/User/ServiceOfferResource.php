<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ServiceOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $minPrice = $this->effectivePriceFrom();

        return [
            'id' => $this->id,
            'service_provider_id' => $this->service_provider_id,
            'service_category_id' => $this->service_category_id,
            'title' => (string) ($this->title ?? ''),
            'type' => $this->type?->value ?? $this->type,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'cover' => $this->cover ? FileManager::getUrl($this->cover) : null,
            'description' => (string) ($this->description ?? ''),
            'price_from' => $minPrice,
            'milestone_available' => (bool) $this->milestone_available,
            'plans' => $this->whenLoaded('plans', fn() => $this->plans->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'price' => (float) $p->price,
                'currency' => $p->currency,
                'estimated_delivery_time' => [
                    'value' => $p->delivery_time_value,
                    'unit' => $p->delivery_time_unit?->value ?? $p->delivery_time_unit
                ],
                'revision_window' => $p->revision_window,
                'number_of_revisions' => $p->number_of_revisions,
                'revision_scope_rules' => $p->revision_scope_rules,
            ])->values()->all()),
            'faqs' => $this->whenLoaded('faqs', fn() => $this->faqs->map(fn($f) => [
                'id' => $f->id,
                'question' => $f->question,
                'answer' => $f->answer,
            ])->values()->all()),
            'available_slots' => $this->whenLoaded('availableSlots', function () {
                $grouped = $this->availableSlots->groupBy('day');
                return $grouped->map(fn($slots, $day) => [
                    'day' => $day,
                    'slots' => $slots->map(fn($s) => [
                        'id' => $s->id,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                    ])->values()->all(),
                ])->values()->all();
            }),
            'service_category' => $this->whenLoaded('serviceCategory', fn() => [
                'id' => $this->serviceCategory->id,
                'name' => (string) ($this->serviceCategory->getTranslation('name', app()->getLocale()) ?? $this->serviceCategory->getTranslation('name', config('app.fallback_locale')) ?? ''),
                'slug' => $this->serviceCategory->slug,
            ]),
            'service_provider' => $this->whenLoaded('serviceProvider', fn() => [
                'id' => $this->serviceProvider->id,
                'display_name' => (string) ($this->serviceProvider->display_name ?? ''),
                'region' => (string) ($this->serviceProvider->region ?? ''),
                'country_code' => (string) ($this->serviceProvider->country_code ?? ''),
            ]),
            'is_sponsored' => $this->isPublicSponsoredListing(),
            'boost_score' => round($this->publicBoostScore(), 4),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
