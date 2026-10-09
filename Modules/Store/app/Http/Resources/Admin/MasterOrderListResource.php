<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'total_price' => (float) $this->total_price,

            'status' => $this->status?->value ?? $this->status,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            'stores' => $this->whenLoaded('storeOrders', function () {
                return $this->storeOrders
                    ->pluck('store')
                    ->filter()
                    ->unique('id')
                    ->map(function ($store) {
                        return [
                            'id' => $store->id,
                            'name' => $store->name,
                        ];
                    })
                    ->values();
            }),

            'store_orders_count' => $this->whenCounted('storeOrders'),

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
