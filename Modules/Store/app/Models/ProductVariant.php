<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'low_stock_threshold',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues()
    {
        return $this->belongsToMany(
            ProductVariantOptionValue::class,
            'product_variant_option_value_variant',
            'variant_id',
            'option_value_id'
        );
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function flashSales()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_products', 'variant_id', 'flash_sale_id')
            ->withTimestamps();
    }
}
