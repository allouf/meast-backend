<?php

namespace Modules\Store\Http\Resources\Admin;

use Modules\Store\Http\Resources\User\UserAddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = null;
        if ($this->relationLoaded('masterOrder') && $this->masterOrder) {
            if ($this->masterOrder->relationLoaded('user') && $this->masterOrder->user) {
                $userData = $this->masterOrder->user;
                $user = [
                    'id' => $userData->id ?? null,
                    'name' => $userData->name ?? null,
                    'email' => $userData->email ?? null,
                    'phone' => $userData->phone ?? null,
                ];
            }
        }
        $address = null;
        if ($this->storeOrders && $this->storeOrders->isNotEmpty()) {
            $firstStoreOrder = $this->storeOrders->first();
            if ($firstStoreOrder && $firstStoreOrder->address) {
                $address = new UserAddressResource($firstStoreOrder->address);
            }
        }
        return [
            'id' => $this->id ?? null,
            'master_order_id' => $this->master_order_id ?? null,
            'store_id' => $this->store_id ?? null,
            'subtotal' => $this->subtotal ? (float) $this->subtotal : 0.0,
            'payment_method' => $this->payment_method ?? null,
            'shipping_method' => $this->shipping_method ?? null,
            'address' => $address,
            'status' => $this->status ?? null,
            'user' => $user,
            'store' => $this->when($this->relationLoaded('store') && $this->store, function () {
                return [
                    'id' => $this->store->id ?? null,
                    'name' => $this->store->name ?? null,
                    'slug' => $this->store->slug ?? null,
                ];
            }, null),
            'items' => $this->when($this->relationLoaded('items') && $this->items, function () {
                return OrderItemResource::collection($this->items);
            }, []),
            'items_count' => $this->items ? $this->items->count() : 0,
            'products_count' => $this->when($this->relationLoaded('items') && $this->items, function () {
                $productIds = $this->items->filter(function ($item) {
                    return $item->variant && $item->variant->product_id;
                })->map(function ($item) {
                    return $item->variant->product_id;
                })->unique();
                return $productIds->count();
            }, 0),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
