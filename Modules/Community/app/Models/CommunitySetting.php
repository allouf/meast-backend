<?php

namespace Modules\Community\Models;

use Illuminate\Database\Eloquent\Model;

class CommunitySetting extends Model
{
    protected $fillable = [
        'community_status',
        'max_post_length',
        'reels_duration_seconds',
        'interaction_window_hours',
    ];

    protected $casts = [
        'community_status' => 'boolean',
        'max_post_length' => 'integer',
        'reels_duration_seconds' => 'integer',
        'interaction_window_hours' => 'integer',
    ];

    public static function singleton(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
