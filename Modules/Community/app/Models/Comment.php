<?php

namespace Modules\Community\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'parent_id',
        'content',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['user', 'commentable'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPost($query, $postId)
    {
        return $query->where('commentable_type', Post::class)->where('commentable_id', $postId);
    }

    public function scopeByReel($query, $reelId)
    {
        return $query->where('commentable_type', Reel::class)->where('commentable_id', $reelId);
    }

    public function scopeByStory($query, $storyId)
    {
        return $query->where('commentable_type', Story::class)->where('commentable_id', $storyId);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getRepliesCountAttribute()
    {
        return $this->replies()->count();
    }

    public function getIsReplyAttribute()
    {
        return !is_null($this->parent_id);
    }
}
