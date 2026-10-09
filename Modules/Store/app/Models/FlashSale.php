<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FlashSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'max_per_user',
        'discount_value',
        'discount_type',
        'status',
    ];

    protected $casts = [
        'start_date'    => 'datetime',
        'end_date'      => 'datetime',
        'discount_value' => 'decimal:2',
    ];


    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'flash_sale_products',
            'flash_sale_id',
            'variant_id'
        )->withTimestamps();
    }


    // public function products()
    // {
    //     return Product::whereIn('id', function ($query) {
    //         $query->select('product_variants.product_id')
    //             ->from('product_variants')
    //             ->join('flash_sale_products', 'product_variants.id', '=', 'flash_sale_products.variant_id')
    //             ->whereColumn('flash_sale_products.flash_sale_id', 'flash_sales.id');
    //     });
    // }


    public function isActive(): bool
    {
        $now = Carbon::now();
        return $this->status === 'active' && $now->between($this->start_date, $this->end_date);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
