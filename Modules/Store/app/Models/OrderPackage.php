<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPackage extends Model
{
    protected $fillable = [
        'store_order_id',
        'product_package_id',
        'quantity',
        'price',
        'subtotal',
    ];

    public function storeOrder()
    {
        return $this->belongsTo(StoreOrder::class);
    }

    public function package()
    {
        return $this->belongsTo(ProductPackage::class, 'product_package_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_package_id');
    }
}
