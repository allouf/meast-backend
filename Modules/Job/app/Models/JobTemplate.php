<?php

namespace Modules\Job\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id', 'category_id', 'title', 'is_system_template', 'is_active',
    ];

    protected $casts = [
        'is_system_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function category()
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function fields()
    {
        return $this->hasMany(JobTemplateField::class)->orderBy('sort_order');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'job_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system_template', true);
    }
}
