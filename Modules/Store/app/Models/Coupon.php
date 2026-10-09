<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'code',
        'discount_type',
        'discount_value',
        'min_cart_amount',
        'expires_at',
        'max_usage_count',
        'max_usage_per_user',
        'scope',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /** 🔗 العلاقات **/

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'coupon_category')
                    ->withTimestamps();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'coupon_product')
                    ->withTimestamps();
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /** 🔍 دوال منطقية إضافية **/

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function remainingUsage(): ?int
    {
        if (is_null($this->max_usage_count)) {
            return null;
        }
        return $this->max_usage_count - $this->usages()->count();
    }

}
