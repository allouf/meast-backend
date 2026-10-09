<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantOptionValue extends Model
{
    protected $fillable = ['option_id', 'value'];

    public function option()
    {
        return $this->belongsTo(ProductVariantOption::class, 'option_id');
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_option_value_variant',
            'option_value_id',
            'variant_id'
        );
    }
}
