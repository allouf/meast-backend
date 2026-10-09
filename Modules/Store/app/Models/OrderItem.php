<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'store_order_id',
        'product_variant_id',
        'quantity',
        'price',
        'subtotal',
        'is_from_package',
        'order_package_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_from_package' => 'boolean',

    ];

    /**
     * Get the store order that owns this order item.
     */
    public function storeOrder()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function orderPackage()
    {
        return $this->belongsTo(OrderPackage::class, 'order_package_id');
    }

    public function scopeForCategoryIds(Builder $query, array $categoryIds): Builder
    {
        return $query->whereHas('variant.product', function ($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds);
        });
    }
}
