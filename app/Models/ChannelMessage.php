<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMessage extends Model
{
    protected $fillable = [
        'channel_id',
        'sender_id',
        'message',
        'message_type',
        'read_by',
        'is_pinned',
        'reply_to'
    ];

    protected $casts = [
        'read_by' => 'array',
        'is_pinned' => 'boolean'
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ChannelMessage::class, 'reply_to');
    }

    public function replies()
    {
        return $this->hasMany(ChannelMessage::class, 'reply_to');
    }

    public function markAsRead($userId): void
    {
        $readBy = $this->read_by ?? [];
        if (!in_array($userId, $readBy)) {
            $readBy[] = $userId;
            $this->update(['read_by' => $readBy]);
        }
    }

    public function isReadBy($userId): bool
    {
        $readBy = $this->read_by ?? [];
        return in_array($userId, $readBy);
    }
}
