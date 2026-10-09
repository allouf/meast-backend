<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Store\Enums\ProductStatus;
use Modules\Store\Models\Scopes\ProductLocationScope;

class Product extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new ProductLocationScope());
    }
    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'description',
        'specifications',
        'additional_features',
        'price',
        'price_before_discount',
        'discount_value',
        'discount_type',
        'number_of_buys',
        'avg_rating',
        'rating_count',
        'slug',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_before_discount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'avg_rating' => 'float',
        'rating_count' => 'integer',
        'number_of_buys' => 'integer',
        'status' => ProductStatus::class,
        'additional_features' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ProductGallery::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class);
    }
    public function flashSales()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_products')
            ->withPivot('discount_price', 'discount_type')
            ->withTimestamps();
    }


    public function getFinalPriceAttribute(): float
    {
        if (!$this->discount_value || !$this->discount_type) {
            return (float) $this->price;
        }

        if ($this->discount_type === 'percentage') {
            $discounted = $this->price - ($this->price * ($this->discount_value / 100));
        } else {
            $discounted = $this->price - $this->discount_value;
        }

        return max($discounted, 0);
    }


    public function getFlashPrice(): ?float
    {
        $flashSale = $this->flashSales()->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('priority', 'desc')
            ->first();

        return $flashSale ? $flashSale->pivot->discount_price : null;
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get all packages/bundles for this product
     */
    public function packages(): HasMany
    {
        return $this->hasMany(ProductPackage::class);
    }
    /**
     * Get only active packages/bundles for this product
     */
    public function activePackages(): HasMany
    {
        return $this->packages()->where('is_active', true);
    }
}
