<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ProductAutocompleteResource extends JsonResource
{
    /**
     * Transform the resource into an array for autocomplete.
     * Returns minimal data for fast autocomplete responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get first gallery image as thumbnail
        $thumbnail = null;
        if ($this->relationLoaded('gallery') && $this->gallery) {
            $galleryCollection = is_array($this->gallery) ? collect($this->gallery) : $this->gallery;
            $firstImage = $galleryCollection->first();
            if ($firstImage && isset($firstImage->file_path)) {
                $thumbnail = FileManager::getUrl($firstImage->file_path);
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'price_before_discount' => (float) ($this->price_before_discount ?? $this->price),
            'thumbnail' => $thumbnail,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
        ];
    }
}
