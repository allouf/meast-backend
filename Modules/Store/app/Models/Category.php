<?php

namespace Modules\Store\Models;

use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, NodeTrait, HasTranslations;

    protected $fillable = [
        'name',
        'icon',
        'sort_order',
        'parent_id',
        'is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public array $translatable = ['name'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Get the products for the category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_categories');
    }

}
