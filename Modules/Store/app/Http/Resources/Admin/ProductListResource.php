<?php

namespace Modules\Store\Http\Resources\Admin;

use Modules\Store\Enums\ProductStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $userId = auth()->id();

        $store = $this->when($this->relationLoaded('store') && $this->store, $this->store, null);

        return [
            'id' => $this->id ?? null,
            'store_id' => $this->store_id ?? null,
            'store' => $store ? [
                'id' => $store->id ?? null,
                'name' => $store->name ?? null,
                'image' => $store->logo ? FileManager::getUrl($store->logo) : null,
            ] : null,
            'category' => $this->when($this->relationLoaded('category') && $this->category, function () {
                return new CategoryResource($this->category);
            }, null),
            'name' => $this->name ?? null,
            'slug' => $this->slug ?? null,
            'price' => $this->price ? (float) $this->price : 0.0,
            'price_before_discount' => $this->price_before_discount ? (float) $this->price_before_discount : ($this->price ? (float) $this->price : 0.0),
            'discount_value' => $this->discount_value ? (float) $this->discount_value : 0.0,
            'discount_type' => $this->discount_type ?? null,
            'status' => $this->status ?? null,
            'number_of_buys' => $this->number_of_buys ? (int) $this->number_of_buys : 0,
            'avg_rating' => $this->avg_rating ? (float) $this->avg_rating : 0.0,
            'total_left_in_stock' => $this->total_stock ? (int) $this->total_stock : 0,
            'availability_status' => match (true) {
                $this->status !== ProductStatus::ACTIVE => 'unavailable',
                ((int) ($this->total_stock ?? 0)) === 0 => 'sold_out',
                ((int) ($this->total_stock ?? 0)) <= 5 => 'low_stock',
                default => 'in_stock',
            },
            'gallery' => $this->when($this->relationLoaded('gallery') && $this->gallery, function () {
                $firstImage = $this->gallery->first();
                if ($firstImage) {
                    return [[
                        'id' => $firstImage->id ?? null,
                        'file_path' => isset($firstImage->file_path) ? FileManager::getUrl($firstImage->file_path) : null,
                        'file_type' => $firstImage->file_type ?? null,
                    ]];
                }
                return [];
            }, []),
            'specifications' => $this->when(
                $request->is('api/v1/wishlists*'),
                $this->specifications ?? null,
                null
            ),
            'is_favorited' => $userId
                ? $this->wishlists()->where('user_id', $userId)->exists()
                : false,
            'favorited_at' => $userId
                ? $this->wishlists()->where('user_id', $userId)->value('created_at')?->toIso8601String()
                : null,
        ];
    }
}
