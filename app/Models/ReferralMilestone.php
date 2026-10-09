<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ReferralMilestone extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];
    protected $fillable = [
        'name',
        'required_points',
        'reward_type',
        'reward_value',
        'valid_days',
    ];
}
