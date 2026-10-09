<?php

namespace Modules\Advertisement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AdvertisementSubcategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'advertisement_category_id',
        'name',
        'slug',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public array $translatable = ['name'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function category()
    {
        return $this->belongsTo(AdvertisementCategory::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }
}
