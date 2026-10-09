<?php

namespace Modules\Job\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active', 'sort_order'];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the name for a given locale (e.g. 'en', 'ar'), fallback to 'en'.
     */
    public function getNameForLocale(string $locale = 'en'): ?string
    {
        $name = $this->name;
        if (! is_array($name)) {
            return null;
        }
        return $name[$locale] ?? $name['en'] ?? reset($name);
    }
}
