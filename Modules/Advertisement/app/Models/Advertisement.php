<?php

namespace Modules\Advertisement\Models;

use App\Utilities\FileManager;
use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Advertisement\Enums\AdvertisementStatus;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'advertisement_category_id',
        'advertisement_subcategory_id',
        'country_code',
        'region',
        'title',
        'slug',
        'description',
        'price',
        'is_price_negotiable',
        'is_free',
        'condition',
        'status',
        'contact_method',
        'phone',
        'show_phone',
        'images',
        'views_count',
        'published_at',
        'expires_at',
        'renewed_at',
        'attributes'
    ];

    protected $casts = [
        'is_price_negotiable' => 'boolean',
        'is_free' => 'boolean',
        'show_phone' => 'boolean',
        'images' => 'array',
        'attributes' => 'array',
        'price' => 'decimal:2',
        'views_count' => 'integer',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['image_urls'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($advertisement) {
            if (empty($advertisement->slug)) {
                $advertisement->slug = Str::slug($advertisement->title) . '-' . time();
            }
            if ($advertisement->is_free) {
                $advertisement->price = 0;
            }
        });

        static::updating(function ($advertisement) {
            if ($advertisement->is_free) {
                $advertisement->price = 0;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(AdvertisementCategory::class, 'advertisement_category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(AdvertisementSubcategory::class, 'advertisement_subcategory_id');
    }

    public function favorites()
    {
        return $this->hasMany(AdvertisementFavorite::class);
    }

    public function reports()
    {
        return $this->hasMany(AdvertisementReport::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'advertisement_favorites')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', AdvertisementStatus::ACTIVE->value)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopePublished($query)
    {
        return $query->where('status', AdvertisementStatus::ACTIVE->value)
            ->where('published_at', '<=', now());
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('advertisement_category_id', $categoryId);
    }

    public function scopeBySubcategory($query, $subcategoryId)
    {
        return $query->where('advertisement_subcategory_id', $subcategoryId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', '!=', AdvertisementStatus::EXPIRED->value);
    }

    public function getImageUrlsAttribute()
    {
        if (!$this->images || !is_array($this->images)) {
            return [];
        }

        return array_map(function ($image) {
            return FileManager::getUrl($image);
        }, $this->images);
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canRenew()
    {
        if (!$this->renewed_at) {
            return true;
        }
        return $this->renewed_at->addDay()->isPast();
    }

    public function renew()
    {
        if (!$this->canRenew()) {
            return false;
        }

        $this->update([
            'published_at' => now(),
            'renewed_at' => now(),
            'status' => AdvertisementStatus::ACTIVE->value,
        ]);

        return true;
    }
}
