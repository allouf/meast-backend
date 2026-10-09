<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total_price' => (float) $this->total_price,
            'is_active' => (bool) $this->is_active,
            'items' => $this->items ? CartItemResource::collection($this->items) : [],
            'items_count' => $this->items ? $this->items->count() : 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

