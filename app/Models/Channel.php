<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'avatar',
        'privacy',
        'is_active',
        'allow_member_messages',
        'allow_member_media',
        'allow_member_calls',
        'allow_member_invites',
        'require_approval',
        'max_members',
        'read_only'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_member_messages' => 'boolean',
        'allow_member_media' => 'boolean',
        'allow_member_calls' => 'boolean',
        'allow_member_invites' => 'boolean',
        'require_approval' => 'boolean',
        'read_only' => 'boolean'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_members')
            ->withPivot(['role', 'status', 'joined_at', 'last_read_at', 'is_online'])
            ->withTimestamps();
    }

    public function channelMembers(): HasMany
    {
        return $this->hasMany(ChannelMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class);
    }

    public function callDetails(): HasMany
    {
        return $this->hasMany(ChannelCallDetail::class);
    }

    public function memberRequests(): HasMany
    {
        return $this->hasMany(ChannelMemberRequest::class);
    }

    public function isOwner($userId): bool
    {
        return $this->owner_id === $userId;
    }

    public function isAdmin($userId): bool
    {
        return $this->channelMembers()
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function isMember($userId): bool
    {
        return $this->channelMembers()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function canSendMessage($userId): bool
    {
        if ($this->read_only) {
            return $this->isOwner($userId);
        }

        if ($this->allow_member_messages) {
            return $this->isMember($userId);
        }

        return $this->isOwner($userId) || $this->isAdmin($userId);
    }

    public function canUploadMedia($userId): bool
    {
        if ($this->allow_member_media) {
            return $this->isMember($userId);
        }

        return $this->isOwner($userId) || $this->isAdmin($userId);
    }

    public function canMakeCall($userId): bool
    {
        if ($this->allow_member_calls) {
            return $this->isMember($userId);
        }

        return $this->isOwner($userId) || $this->isAdmin($userId);
    }

    public function getUnreadCount($userId): int
    {
        $lastReadAt = $this->channelMembers()
            ->where('user_id', $userId)
            ->value('last_read_at');

        return $this->messages()
            ->when($lastReadAt, function ($query) use ($lastReadAt) {
                $query->where('created_at', '>', $lastReadAt);
            })
            ->where('sender_id', '!=', $userId)
            ->count();
    }
}
