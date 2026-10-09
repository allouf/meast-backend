<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantOption extends Model
{
    protected $fillable = ['product_id', 'name', 'type'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->hasMany(ProductVariantOptionValue::class, 'option_id');
    }
}
