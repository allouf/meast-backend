<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashSaleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'start_date'     => $this->start_date->format('Y-m-d H:i:s'),
            'end_date'       => $this->end_date->format('Y-m-d H:i:s'),
            'max_per_user'   => $this->max_per_user,
            'discount_type'  => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'status'         => $this->status,
            'product_count' => $this->product_count ?? 0,
            // 'is_active'      => $this->isActive(),
        ];
    }
}
