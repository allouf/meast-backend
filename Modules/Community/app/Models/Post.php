<?php

namespace Modules\Community\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\FileManager;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country_code',
        'region',
        'content',
        'image',
        'status',
        'type',
        'is_featured'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['user'];
    protected $appends = ['reactions_count', 'comments_count', 'image_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->where('parent_id', null);
    }

    public function reports()
    {
        return $this->hasMany(PostReport::class);
    }

    public function logs()
    {
        return $this->hasMany(PostLog::class);
    }

    public function allComments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reactions()
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    public function likes()
    {
        return $this->reactions()->where('type', 'like');
    }

    public function loves()
    {
        return $this->reactions()->where('type', 'love');
    }

    public function hahas()
    {
        return $this->reactions()->where('type', 'haha');
    }

    public function wows()
    {
        return $this->reactions()->where('type', 'wow');
    }

    public function sads()
    {
        return $this->reactions()->where('type', 'sad');
    }

    public function angries()
    {
        return $this->reactions()->where('type', 'angry');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getReactionsCountAttribute()
    {
        return $this->reactions()->count();
    }

    public function getCommentsCountAttribute()
    {
        return $this->allComments()->count();
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return FileManager::getUrl($this->image);
        }
        return null;
    }
}
