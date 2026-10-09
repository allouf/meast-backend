<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class ViolationReason extends Model
{
    protected $fillable = [
        'title',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function violations()
    {
        return $this->hasMany(Violation::class, 'reason_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('title');
    }
}
