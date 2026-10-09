<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use App\Utilities\FileManager;


class ProductDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = auth()->id();

        // Safely get store and category
        $store = null;
        if ($this->relationLoaded('store')) {
            $storeValue = $this->resource->getRelation('store');
            if ($storeValue && !($storeValue instanceof MissingValue) && method_exists($storeValue, 'getKey')) {
                $store = $storeValue;
            }
        }

        $category = null;
        if ($this->relationLoaded('category')) {
            $categoryValue = $this->resource->getRelation('category');
            if ($categoryValue && !($categoryValue instanceof MissingValue) && method_exists($categoryValue, 'getKey')) {
                $category = $categoryValue;
            }
        }

        // Handle packages safely
        $packages = [];
        if ($this->relationLoaded('packages') && $this->packages) {
            $this->packages->loadMissing([
                'items.product',
                'items.product',
                'items.product.options.values',
                'items.product.variants.optionValues',
                'items.variant',
                'items.variant.optionValues'
            ]);
            $packages = PackageResource::collection($this->packages);
        }

        return [
            'id' => $this->id ?? null,
            'store_id' => $this->store_id ?? null,
            'category_id' => $this->category_id ?? null,
            'name' => $this->name ?? null,
            'slug' => $this->slug ?? null,
            'description' => $this->description ?? null,
            'specifications' => $this->specifications ?? null,
            'additional_features' => $this->additional_features ?? null,
            'price' => $this->price ? (float) $this->price : 0.0,
            'price_before_discount' => $this->price_before_discount ? (float) $this->price_before_discount : ($this->price ? (float) $this->price : 0.0),
            'discount_value' => $this->discount_value ? (float) $this->discount_value : 0.0,
            'discount_type' => $this->discount_type ?? null,
            'number_of_buys' => $this->number_of_buys ? (int) $this->number_of_buys : 0,
            'avg_rating' => $this->avg_rating ? (float) $this->avg_rating : 0.0,
            'status' => $this->status ?? null,
            'gallery' => $this->when($this->relationLoaded('gallery') && $this->gallery, function () {
                return ProductGalleryResource::collection($this->gallery);
            }, []),
            'variants' => $this->when($this->relationLoaded('variants') && $this->variants, function () {
                return ProductVariantResource::collection($this->variants);
            }, []),
            'options' => $this->when($this->relationLoaded('options') && $this->options, function () {
                return ProductVariantOptionResource::collection($this->options);
            }, []),
            'store' => $store ? [
                'id' => $store->id ?? null,
                'name' => $store->name ?? null,
                'logo' => $store->logo ? FileManager::getUrl($store->logo) : null,
            ] : null,
            'category' => $category ? [
                'id' => $category->id ?? null,
                'name' => $category->name ?? null,
                'icon' => $category->icon ? FileManager::getUrl($category->icon) : null,
            ] : null,
            'reviews' => $this->when($this->relationLoaded('reviews') && $this->reviews, function () {
                return ProductReviewResource::collection($this->reviews->where('is_approved', true));
            }, []),
            'packages' => $packages,
            'is_favorited' => $userId
                ? $this->wishlists()->where('user_id', $userId)->exists()
                : false,
            'favorited_at' => $userId
                ? $this->wishlists()->where('user_id', $userId)->value('created_at')?->toIso8601String()
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
