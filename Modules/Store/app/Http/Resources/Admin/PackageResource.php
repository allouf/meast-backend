<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = [];
        $totalPrice = 0;

        if ($this->relationLoaded('items') && $this->items) {
            $items = $this->items->map(function ($item) use (&$totalPrice) {
                $itemData = [
                    'id' => $item->id ?? null,
                    'variant_id' => $item->variant_id ?? null,
                    'product' => $item->product ? [
                        'id' => $item->product->id ?? null,
                        'title' => $item->product->name ?? null,
                        'avg_rating' => $item->product->avg_rating ? (float) $item->product->avg_rating : 0.0,
                        'rating_count' => $item->product->rating_count ? (int) $item->product->rating_count : 0,
                        'category' => $item->product->relationLoaded('category') && $item->product->category
                            ? new CategoryResource($item->product->category)
                            : null,
                    ] : null,
                    'quantity' => $item->quantity ? (int) $item->quantity : 0,
                ];

                $variantPrice = 0;
                if ($item->relationLoaded('variant') && $item->variant) {
                    $variantPrice = (float) $item->variant->price;
                    $variantResource = new ProductVariantResource($item->variant);
                    $itemData['variant'] = $variantResource->toArray(request());
                } else {
                    $itemData['variant'] = [
                        'id' => null,
                        'product_id' => null,
                        'sku' => null,
                        'price' => 0.0,
                        'stock' => 0,
                        'low_stock_threshold' => 0,
                        'image' => null,
                        'is_active' => false,
                        'option_values' => [],
                        'options' => [],
                    ];
                }

                $itemTotal = $variantPrice * (int) $item->quantity;
                $itemData['total_price'] = round($itemTotal, 2);

                // إضافته إلى مجموع الحزمة
                $totalPrice += $itemTotal;

                return $itemData;
            });
        }

        return [
            'id' => $this->id ?? null,
            'product' => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'description' => $this->product->description,
                'price' => $this->product->price,
                'image' => $this->when(
                    $this->product->relationLoaded('gallery') && $this->product->gallery && $this->product->gallery->isNotEmpty(),
                    function () {
                        $firstImage = $this->product->gallery->first();
                        return $firstImage && isset($firstImage->file_path) ? FileManager::getUrl($firstImage->file_path) : null;
                    },
                    null
                )
            ] : null,
            'store_id' => $this->store_id ?? null,
            'title' => $this->title ?? null,
            'description' => $this->description ?? null,
            'discount_type' => $this->discount_percent
                ? 'percent'
                : ($this->fixed_discount ? 'fixed' : 'none'),
            'discount_percent' => $this->discount_percent ? (float) $this->discount_percent : null,
            'fixed_discount' => $this->fixed_discount ? (float) $this->fixed_discount : null,
            'is_active' => (bool) ($this->is_active ?? false),
            'items' => $items,
            'total_price' => round($totalPrice, 2),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
