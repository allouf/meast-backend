<?php

namespace Modules\Services\Http\Resources\Provider;

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
        return [
            'id' => $this->id,
            'service_provider_id' => $this->service_provider_id,
            'service_category_id' => $this->service_category_id,
            'title' => (string) ($this->title ?? ''),
            'type' => $this->type?->value ?? $this->type,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'description' => (string) ($this->description ?? ''),
            'uses_base_pricing' => $this->usesBasePricing(),
            'base_price' => $this->base_price !== null ? (float) $this->base_price : null,
            'base_currency' => $this->base_currency,
            'base_delivery_time_value' => $this->base_delivery_time_value,
            'base_delivery_time_unit' => $this->base_delivery_time_unit?->value ?? $this->base_delivery_time_unit,
            'base_number_of_revisions' => $this->base_number_of_revisions,
            'base_revision_window' => $this->base_revision_window,
            'milestone_available' => (bool) $this->milestone_available,

            'cover' => $this->whenLoaded('media', function () {
                $coverMedia = $this->media->firstWhere('is_cover', true);

                return $coverMedia
                    ? [
                        'id' => $coverMedia->id,
                        'url' => FileManager::getUrl($coverMedia->media_path),
                        'type' => $coverMedia->media_type,
                    ]
                    : null;
            }),
            
            'media' => ServiceOfferMediaResource::collection($this->whenLoaded('media')),

            'plans' => $this->whenLoaded('plans', fn() => $this->plans->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'price' => (float) $p->price,
                'currency' => $p->currency,
                'delivery_time_value' => $p->delivery_time_value,
                'delivery_time_unit' => $p->delivery_time_unit?->value ?? $p->delivery_time_unit,
                'delivery_time' => $p->delivery_time_value && $p->delivery_time_unit
                    ? $p->delivery_time_value . ' ' . $p->delivery_time_unit->value
                    : null,
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
            'is_active' => $this->is_active,
            'status' => $this->status?->value ?? $this->getRawOriginal('status') ?? 'active',
            'sort_order' => $this->sort_order,
            'service_category' => $this->whenLoaded('serviceCategory', fn() => [
                'id' => $this->serviceCategory->id,
                'name' => (string) ($this->serviceCategory->getTranslation('name', app()->getLocale()) ?? $this->serviceCategory->getTranslation('name', config('app.fallback_locale')) ?? ''),
                'slug' => $this->serviceCategory->slug,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
