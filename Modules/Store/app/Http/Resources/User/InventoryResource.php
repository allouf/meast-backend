<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get first gallery image
        $firstGalleryImage = null;
        if ($this->relationLoaded('gallery') && $this->gallery && $this->gallery->isNotEmpty()) {
            $firstImage = $this->gallery->first();
            if ($firstImage && isset($firstImage->file_path)) {
                $firstGalleryImage = FileManager::getUrl($firstImage->file_path);
            }
        }

        return [
            'id' => $this->id ?? null,
            'name' => $this->name ?? null,
            'description' => $this->description ?? null,
            'image' => $firstGalleryImage ?? null,
            'variants' => $this->when($this->relationLoaded('variants') && $this->variants, function () {
                return $this->variants->map(function ($variant) {
                    $variantResource = new ProductVariantResource($variant);
                    $variantArray = $variantResource->toArray(request());
                    
                    return array_merge($variantArray, [
                        'stock_status' => $this->getStockStatusForVariant($variant),
                        'updated_at' => $variant->updated_at?->toIso8601String(),
                    ]);
                })->values();
            }, []),
        ];
    }

    /**
     * Determine stock status based on stock for a variant.
     */
    protected function getStockStatusForVariant($variant): string
    {
        if ($variant->stock <= 0) {
            return __('store::messages.out_of_stock');
        }

        if (!empty($variant->low_stock_threshold) && $variant->stock <= $variant->low_stock_threshold) {
            return __('store::messages.low_stock');
        }

        return __('store::messages.in_stock');
    }
}
