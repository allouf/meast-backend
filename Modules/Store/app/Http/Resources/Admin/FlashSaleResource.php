<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashSaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id ?? null,
            'name'           => $this->name ?? null,
            'description'    => $this->description ?? null,
            'start_date'     => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date'       => $this->end_date?->format('Y-m-d H:i:s'),
            'max_per_user'   => $this->max_per_user ?? null,
            'discount_type'  => $this->discount_type ?? null,
            'discount_value' => $this->discount_value ? (float) $this->discount_value : 0.0,
            'status'         => $this->status ?? null,
            // 'is_active'      => $this->isActive() ?? false,
            'store' => $this->when($this->relationLoaded('store') && $this->store, function () {
                return [
                    'id'   => $this->store->id ?? null,
                    'name' => $this->store->name ?? null,
                ];
            }, null),
            'products' => $this->when($this->relationLoaded('variants') && $this->variants, function () {
                $this->variants->loadMissing([
                    'product.options.values',
                    'optionValues',
                ]);

                return $this->variants
                    ->groupBy(fn($variant) => $variant->product->id ?? null)
                    ->map(function ($variants) {
                        $product = $variants->first()->product ?? null;

                        if (!$product) {
                            return null;
                        }

                        return [
                            'id'       => $product->id ?? null,
                            'name'     => $product->name ?? null,
                            'variants' => ProductVariantResource::collection($variants),
                        ];
                    })
                    ->filter()
                    ->values();
            }, []),
            'product_count' => $this->when($this->relationLoaded('variants') && $this->variants, function () {
                return $this->variants->pluck('product_id')->unique()->count();
            }, 0),

        ];
    }
}
