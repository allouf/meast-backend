<?php

namespace Modules\Job\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class JobCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['name', 'slug', 'description', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public array $translatable = ['name', 'description'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (JobCategory $jobCategory): void {
            if (empty($jobCategory->slug) && ! empty($jobCategory->name)) {
                $jobCategory->slug = Str::slug($jobCategory->name);
            }
        });
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'category_id');
    }

    public function jobTemplates()
    {
        return $this->hasMany(JobTemplate::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
