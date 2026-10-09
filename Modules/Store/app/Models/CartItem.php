<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'package_id', 'product_id', 'product_variant_id', 'quantity', 'price', 'subtotal'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function package()
    {
        return $this->belongsTo(ProductPackage::class, 'package_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

