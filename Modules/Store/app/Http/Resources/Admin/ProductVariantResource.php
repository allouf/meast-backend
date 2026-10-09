<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'product_id' => $this->product_id ?? null,
            'sku' => $this->sku ?? null,
            'price' => $this->price ? (float) $this->price : 0.0,
            'stock' => $this->stock ? (int) $this->stock : 0,
            'low_stock_threshold' => $this->low_stock_threshold ? (int) $this->low_stock_threshold : 0,
            'image' => $this->image ? FileManager::getUrl($this->image) : null,
            'is_active' => (bool) ($this->is_active ?? false),
            'option_values' => $this->when($this->relationLoaded('optionValues') && $this->optionValues, function () {
                return OptionValueResource::collection($this->optionValues);
            }, []),
            'options' => $this->when(
                $this->relationLoaded('product') && $this->product &&
                $this->product->relationLoaded('options') && $this->product->options,
                function () {
                    return ProductVariantOptionResource::collection($this->product->options);
                },
                []
            ),
        ];
    }
}
