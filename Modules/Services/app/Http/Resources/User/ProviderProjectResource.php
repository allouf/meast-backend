<?php

namespace Modules\Services\Http\Resources\User;

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
            'services' => $this->whenLoaded('serviceOffers', fn () => $this->serviceOffers->map(fn ($service) => [
                'id'    => $service->id,
                'title' => $service->title,
            ])->values()),

            'categories' => $this->whenLoaded(
                'serviceOffers',
                fn() => $this->serviceOffers
                    ->pluck('serviceCategory')
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->map(fn($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
