<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class EndCallEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channelName;
    public $callerId;
    public $receiverId;
    public $conversationId;
    //public $callDetailId;
    public function __construct($channelName, $callerId, $receiverId, $conversationId)
    {
        $this->channelName = $channelName;
        $this->callerId = $callerId;
        $this->receiverId = $receiverId;
        $this->conversationId = $conversationId;
        //$this->callDetailId = $callDetailId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('call.' . $this->receiverId);
    }

    public function broadcastAs()
    {
        return 'end-call';
    }

    public function broadcastWith()
    {
        return [
            'channel_name' => $this->channelName,
            'caller_id' => $this->callerId,
            'conversation_id' => $this->conversationId,
            //'call_detail_id' => $this->callDetailId
        ];
    }
}