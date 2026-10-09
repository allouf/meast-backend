<?php

namespace Modules\Community\Models;

use Illuminate\Database\Eloquent\Model;

class SuggestedContentSetting extends Model
{
    public const TYPES = ['ads', 'posts', 'reels', 'videos'];

    protected $fillable = [
        'content_type',
        'percentage',
        'priority',
    ];

    protected $casts = [
        'percentage' => 'integer',
        'priority'   => 'integer',
    ];
}
