<?php

namespace Modules\Advertisement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AdvertisementCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'icon',
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

    public function subcategories()
    {
        return $this->hasMany(AdvertisementSubcategory::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }
}
