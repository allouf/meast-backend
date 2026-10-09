<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'store_order_id' => $this->store_order_id ?? null,
            'product_variant_id' => $this->product_variant_id ?? null,
            'quantity' => $this->quantity ? (int) $this->quantity : 0,
            'price' => $this->price ? (float) $this->price : 0.0,
            'subtotal' => $this->subtotal ? (float) $this->subtotal : 0.0,
            'variant' => $this->when($this->relationLoaded('variant') && $this->variant, function () {
                return new ProductVariantResource($this->variant);
            }, null),
            'product' => $this->when(
                $this->relationLoaded('variant') && $this->variant &&
                    $this->variant->relationLoaded('product') && $this->variant->product,
                function () {
                    $product = $this->variant->product;
                    return [
                        'id' => $product->id ?? null,
                        'name' => $product->name ?? null,
                        'slug' => $product->slug ?? null,
                        'description' => $product->description ?? null,
                        'price' => $product->price ? (float) $product->price : 0.0,
                        'image' => $this->when(
                            $product->relationLoaded('gallery') && $product->gallery && $product->gallery->isNotEmpty(),
                            function () use ($product) {
                                $firstImage = $product->gallery->first();
                                return $firstImage && isset($firstImage->file_path) ? FileManager::getUrl($firstImage->file_path) : null;
                            },
                            null
                        ),
                        'store' => $this->when(
                            $product->relationLoaded('store') && $product->store,
                            function () use ($product) {
                                return [
                                    'id' => $product->store->id ?? null,
                                    'name' => $product->store->name ?? null,
                                    'slug' => $product->store->slug ?? null,
                                ];
                            },
                            null
                        ),
                    ];
                },
                null
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
