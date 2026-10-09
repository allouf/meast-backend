<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChannelMemberEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channelId;
    public $userId;
    public $action;

    public function __construct($channelId, $userId, $action)
    {
        $this->channelId = $channelId;
        $this->userId = $userId;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel.' . $this->channelId);
    }

    public function broadcastAs()
    {
        return 'channel-member-' . $this->action;
    }

    public function broadcastWith()
    {
        return [
            'channel_id' => $this->channelId,
            'user_id' => $this->userId,
            'action' => $this->action,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
    }
}
