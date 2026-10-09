<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelCallDetail extends Model
{
    protected $fillable = [
        'channel_id',
        'initiator_id',
        'start_call_date',
        'end_call_date',
        'call_type',
        'channel_name',
        'participants',
        'is_active'
    ];

    protected $casts = [
        'start_call_date' => 'datetime',
        'end_call_date' => 'datetime',
        'participants' => 'array',
        'is_active' => 'boolean'
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function addParticipant($userId): void
    {
        $participants = $this->participants ?? [];
        if (!in_array($userId, $participants)) {
            $participants[] = $userId;
            $this->update(['participants' => $participants]);
        }
    }

    public function removeParticipant($userId): void
    {
        $participants = $this->participants ?? [];
        $participants = array_filter($participants, fn($id) => $id !== $userId);
        $this->update(['participants' => array_values($participants)]);
    }

    public function endCall(): void
    {
        $this->update([
            'end_call_date' => now(),
            'is_active' => false
        ]);
    }
}
