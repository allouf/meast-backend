<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class ServiceCategory extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'is_active', 'requires_verification', 'sort_order', 'boost_prohibited'];

    public array $translatable = ['name'];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_verification' => 'boolean',
        'sort_order' => 'integer',
        'boost_prohibited' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (ServiceCategory $category): void {
            if (empty($category->slug) && ! empty($category->name)) {
                $value = is_array($category->name) ? ($category->name['en'] ?? reset($category->name)) : $category->name;
                $category->slug = Str::slug($value ?? '');
            }
        });
        static::updating(function (ServiceCategory $category): void {
            if ($category->isDirty('name') && empty($category->slug)) {
                $value = is_array($category->name) ? ($category->name['en'] ?? reset($category->name)) : $category->name;
                $category->slug = Str::slug($value ?? '');
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
