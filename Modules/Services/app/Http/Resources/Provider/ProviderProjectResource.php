<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ProviderProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_provider_id' => $this->service_provider_id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'images' => $this->images ? array_map(fn ($path) => FileManager::getUrl($path), (array) $this->images) : [],
            'sort_order' => $this->sort_order,
            'service_offers' => $this->whenLoaded('serviceOffers', fn () => $this->serviceOffers->map(fn ($o) => [
                'id' => $o->id,
                'title' => (string) ($o->title ?? ''),
                'type' => $o->type?->value ?? $o->type,
                'delivery_model' => $o->delivery_model?->value ?? $o->delivery_model,
            ])->values()->all()),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
