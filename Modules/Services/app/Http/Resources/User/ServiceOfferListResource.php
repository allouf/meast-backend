<?php

namespace Modules\Services\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\OrderableType;

class ServiceOfferListResource extends JsonResource
{
    /**
     * List/card payload (lightweight).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $minPrice = $this->effectivePriceFrom();

        $cover = null;

        if ($this->relationLoaded('coverMedia') && $this->coverMedia) {
            $cover = [
                'id' => $this->coverMedia->id,
                'url' => FileManager::getUrl($this->coverMedia->media_path),
                'type' => $this->coverMedia->media_type,
            ];
        }

        $deliveryTime = null;

        if ($this->relationLoaded('fastestPlan') && $this->fastestPlan) {
            $deliveryTime = [
                'value' => $this->fastestPlan->delivery_time_value,
                'unit' => $this->fastestPlan->delivery_time_unit?->value
                    ?? $this->fastestPlan->delivery_time_unit,
            ];
        } elseif ($this->base_delivery_time_value !== null && $this->base_delivery_time_unit !== null) {
            $deliveryTime = [
                'value' => $this->base_delivery_time_value,
                'unit' => $this->base_delivery_time_unit?->value ?? $this->base_delivery_time_unit,
            ];
        }

        return [
            'id' => $this->id,
            'orderable_type' => OrderableType::ServiceOffer->value,
            'orderable_id' => $this->id,
            'title' => (string) ($this->title ?? ''),
            'type' => $this->type?->value ?? $this->type,
            'delivery_model' => $this->delivery_model?->value ?? $this->delivery_model,
            'delivery_time' => $deliveryTime,
            'cover' => new ServiceOfferMediaResource($this->whenLoaded('coverMedia')),
            'media' => ServiceOfferMediaResource::collection($this->whenLoaded('media')),
            'description' => (string) ($this->description ?? ''),
            'price_from' => $minPrice,

            'rating' => $this->reviews_avg_rating !== null
                ? round((float) $this->reviews_avg_rating, 1)
                : 0,

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
            /** Effective ranking multiplier applied for this browse response position (snapshot). */
            'boost_score' => round((float) ($this->resource->getAttribute('_boost_multiplier_effective') ?? $this->publicBoostScore()), 4),
            /** True when boosted listing received ranking uplift vs organic tie-break peers for this snapshot. */
            'ranking_boost_applied' => (bool) $this->resource->getAttribute('_ranking_boost_applied'),
            /** Debug / transparency hooks for boosted vs organic decomposition (browse only). */
            'ranking' => $this->when(
                array_key_exists('_base_relevance_score', $this->resource->getAttributes()),
                fn () => [
                    'base_relevance_score' => round((float) $this->resource->getAttribute('_base_relevance_score'), 4),
                    'boost_multiplier_effective' => round((float) $this->resource->getAttribute('_boost_multiplier_effective'), 4),
                ]
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
