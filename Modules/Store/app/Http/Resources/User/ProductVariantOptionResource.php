<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'product_id' => $this->product_id ?? null,
            'name' => $this->name ?? null,
            'type' => $this->type ?? 'text',
            'values' => $this->when($this->relationLoaded('values') && $this->values, function () {
                return OptionValueResource::collection($this->values);
            }, []),
        ];
    }
}
