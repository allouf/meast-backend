<?php

namespace Modules\Store\Http\Resources\Admin;

use Modules\Store\Http\Resources\User\UserAddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $address = null;
        if ($this->storeOrders && $this->storeOrders->isNotEmpty()) {
            $firstStoreOrder = $this->storeOrders->first();
            if ($firstStoreOrder && $firstStoreOrder->address) {
                $address = new UserAddressResource($firstStoreOrder->address);
            }
        }

        return [
            'id' => $this->id ?? null,
            'user_id' => $this->user_id ?? null,
            'total_price' => $this->total_price ? (float) $this->total_price : 0.0,
            'status' => $this->status ?? null,
            'user' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return [
                    'id' => $this->user->id ?? null,
                    'name' => $this->user->name ?? null,
                    'email' => $this->user->email ?? null,
                    'phone' => $this->user->phone ?? null,
                ];
            }, null),
            'address' => $address ? $address->toArray($request) : null,
            'store_orders' => $this->when($this->relationLoaded('storeOrders') && $this->storeOrders, function () {
                return OrderResource::collection($this->storeOrders);
            }, []),
            // 'store_orders_count' => $this->storeOrders ? $this->storeOrders->count() : 0,
            'store_orders_count' => $this->whenCounted('storeOrders'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
