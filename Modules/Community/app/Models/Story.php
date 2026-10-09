<?php

namespace Modules\Community\Models;

use Modules\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\FileManager;

class Story extends Model
{
    use HasFactory;
use App\Utilities\FileManager;

    protected $fillable = [
        'user_id',
        'country_code',
        'region',
        'content',
        'media',
        'status',
        'type',
        'background_color',
        'expires_at',
        'is_featured',
        'views_count'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'expires_at' => 'datetime',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['user'];
    protected $appends = ['reactions_count', 'replies_count', 'media_url', 'is_expired'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($story) {
            if (!$story->expires_at) {
                $story->expires_at = Carbon::now()->addHours(24);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->morphMany(Comment::class, 'commentable')->where('parent_id', null);
    }

    public function allReplies()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reactions()
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    public function viewers()
    {
        return $this->belongsToMany(User::class, 'story_views', 'story_id', 'user_id')
            ->select(['users.id', 'users.name', 'users.photo', 'users.type'])
            ->withPivot('viewed_at')
            ->withTimestamps();
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

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getReactionsCountAttribute()
    {
        return $this->reactions()->count();
    }

    public function getRepliesCountAttribute()
    {
        return $this->allReplies()->count();
    }

    public function getMediaUrlAttribute()
    {
        return $this->media ? FileManager::getUrl($this->media) : null;
    }

    public function getIsExpiredAttribute()
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    public function getViewsCountAttribute()
    {
        return $this->views()->count();
    }

    public function getViewersAttribute()
    {
        return $this->viewers()->get();
    }

    public function hasBeenViewedBy($userId)
    {
        return $this->views()->where('user_id', $userId)->exists();
    }
}
