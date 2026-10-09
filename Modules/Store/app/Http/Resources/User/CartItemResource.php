<?php

namespace Modules\Store\Http\Resources\User;

use Modules\Store\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class CartItemResource extends JsonResource
{
    private function formatCategoryWithParents(?Category $category): ?array
    {
        if (!$category) {
            return null;
        }

        $path = $category->ancestors
            ->sortBy('_lft')
            ->values()
            ->concat(collect([$category]))
            ->map(fn(Category $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);

        return [
            'id' => $category->id,
            'name' => $category->name,
            'path' => $path,
        ];
    }


    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'cart_id' => $this->cart_id ?? null,
            'package_id' => $this->package_id ?? null,
            'product_id' => $this->product_id ?? null,
            'product_variant_id' => $this->product_variant_id ?? null,
            'quantity' => $this->quantity ? (int) $this->quantity : 0,
            'price' => $this->price ? (float) $this->price : 0.0,
            'subtotal' => $this->subtotal ? (float) $this->subtotal : 0.0,
            'package' => $this->when($this->package, function () {
                return [
                    'id' => $this->package->id,
                    'title' => $this->package->title,
                    'description' => $this->package->description,
                    'discount_percent' => $this->package->discount_percent ? (float) $this->package->discount_percent : null,
                    'fixed_discount' => $this->package->fixed_discount ? (float) $this->package->fixed_discount : null,
                    'items' => $this->package->items ? $this->package->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'variant_id' => $item->variant_id,
                            'quantity' => $item->quantity,
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'slug' => $item->product->slug,
                                'price' => (float) $item->product->price,
                            ] : null,
                            'variant' => $item->variant ? [
                                'id' => $item->variant->id,
                                'sku' => $item->variant->sku,
                                'price' => (float) $item->variant->price,
                            ] : null,
                        ];
                    }) : [],
                ];
            }),
            'product' => $this->when($this->product, function () {
                $product = $this->product;
                $store = $product->store ?? null;
                $gallery = $product->gallery ?? null;
                $category = $product->category ?? null;
                
                return [
                    'id' => $product->id ?? null,
                    'name' => $product->name ?? null,
                    'slug' => $product->slug ?? null,
                    'price' => $product->price ? (float) $product->price : 0,
                    'store' => $this->when($store, function () use ($store) {
                        return [
                            'id' => $store->id ?? null,
                            'name' => $store->name ?? null,
                        ];
                    }),

                    'discount_value' => (float) ($product->discount_value ?? 0),
                    'discount_type' => $product->discount_type ?? null,
                    'image' => $this->when(
                        $product->relationLoaded('gallery') && $product->gallery && $product->gallery->isNotEmpty(),
                        function () use ($product) {
                            $firstImage = $product->gallery->first();
                            return $firstImage && isset($firstImage->file_path) ? FileManager::getUrl($firstImage->file_path) : null;
                        },
                        null
                    ),

                    'category' => $this->formatCategoryWithParents($category),
                ];
            }),
            'variant' => $this->when($this->relationLoaded('variant') && $this->variant, function () {
                return new ProductVariantResource($this->variant);
            }, null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
