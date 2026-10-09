<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ProductReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get product image from gallery
        $productImage = null;
        if ($this->relationLoaded('product') && $this->product && $this->product->relationLoaded('gallery')) {
            $firstImage = $this->product->gallery->first();
            if ($firstImage && isset($firstImage->file_path)) {
                $productImage = FileManager::getUrl($firstImage->file_path);
            }
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->when($this->relationLoaded('product') && $this->product, function () use ($productImage) {
                return [
                    'id' => $this->product->id ?? null,
                    'name' => $this->product->name ?? null,
                    'image' => $productImage,
                ];
            }, null),
            'user_id' => $this->user_id,
            'rating' => (int) $this->rating,
            'description' => $this->description,
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'file_path' => FileManager::getUrl($image->file_path),
                        'sort_order' => $image->sort_order,
                    ];
                })->sortBy('sort_order')->values();
            }, []),
            'is_approved' => (bool) $this->is_approved,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
