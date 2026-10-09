<?php

namespace Modules\Job\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;

class UserSkill extends Model
{
    protected $fillable = [
        'user_id',
        'skill_id',
        'skill_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function getDisplayNameAttribute(): ?string
    {
        if ($this->relationLoaded('skill') && $this->skill) {
            return $this->skill->name;
        }
        return $this->skill_name;
    }
}
