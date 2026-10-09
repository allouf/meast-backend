<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductGallery extends Model
{
    protected $table = 'product_gallery';

    protected $fillable = [
        'product_id',
        'file_path',
        'file_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the gallery item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
