<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'title',
        'description',
        'discount_percent',
        'fixed_discount',
        'is_active',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'fixed_discount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the main product that owns this package
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    /**
     * Get the store that owns this package
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get all items in this package
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductPackageItem::class, 'package_id');
    }

    /**
     * Get only active items
     */
    public function activeItems(): HasMany
    {
        return $this->items()->whereHas('product', function ($query) {
            $query->where('status', 'active');
        });
    }
}
