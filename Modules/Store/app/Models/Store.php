<?php

namespace Modules\Store\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Store\Enums\StoreStatus;
use Modules\Store\Models\Scopes\StoreLocationScope;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new StoreLocationScope());
    }

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'cover',
        'description',
        'social_links',
        'phones',
        'emails',
        'address',
        'city',
        'country_code',
        'postal_code',
        'region',
        'latitude',
        'longitude',
        'website',
        'opening_time',
        'closing_time',
        'is_returnable',
        'is_freezone',
        'is_active',
        'rating',
        'status',
        'status_reason',
    ];

    protected $casts = [
        'social_links'   => 'array',
        'phones'         => 'array',
        'emails'         => 'array',
        'latitude'       => 'decimal:7',
        'longitude'      => 'decimal:7',
        'is_returnable'  => 'boolean',
        'is_freezone'    => 'boolean',
        'is_active'      => 'boolean',
        'rating'         => 'decimal:2',
        'status'         => StoreStatus::class,
        'status_reason'  => 'string',
    ];
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'store_categories');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function policies()
    {
        return $this->hasMany(StorePolicy::class);
    }

    public function shippingPolicy()
    {
        return $this->hasOne(StorePolicy::class)->where('type', 'shipping');
    }

    public function returnPolicy()
    {
        return $this->hasOne(StorePolicy::class)->where('type', 'return');
    }

    public function warrantyPolicy()
    {
        return $this->hasOne(StorePolicy::class)->where('type', 'warranty');
    }

    public function members()
    {
        return $this->hasMany(StoreMember::class);
    }

    public function activeMembers()
    {
        return $this->hasMany(StoreMember::class)->where('status', 'active');
    }

    public function invitedMembers()
    {
        return $this->hasMany(StoreMember::class)->where('status', 'invited');
    }
}
