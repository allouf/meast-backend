<?php

namespace Modules\Advertisement\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon ? FileManager::getUrl($this->icon) : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'subcategories' => $this->whenLoaded('subcategories', function () {
                return AdvertisementSubcategoryResource::collection($this->subcategories);
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
