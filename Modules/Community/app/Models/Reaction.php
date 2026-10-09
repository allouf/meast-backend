<?php

namespace Modules\Community\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reactionable_type',
        'reactionable_id',
        'type'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactionable()
    {
        return $this->morphTo();
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPost($query, $postId)
    {
        return $query->where('reactionable_type', Post::class)->where('reactionable_id', $postId);
    }

    public function scopeByReel($query, $reelId)
    {
        return $query->where('reactionable_type', Reel::class)->where('reactionable_id', $reelId);
    }

    public function scopeByStory($query, $storyId)
    {
        return $query->where('reactionable_type', Story::class)->where('reactionable_id', $storyId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getTypeEmojiAttribute()
    {
        $emojis = [
            'like' => '👍',
            'love' => '❤️',
            'haha' => '😂',
            'wow' => '😮',
            'sad' => '😢',
            'angry' => '😠'
        ];

        return $emojis[$this->type] ?? '👍';
    }
}
