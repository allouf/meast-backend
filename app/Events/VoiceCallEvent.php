<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class VoiceCallEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channelName;
    public $callerId;
    public $receiverId;
    public $token;
    public $uid;
    public $conversationId;
    public $callDetailId;

    public function __construct($channelName, $callerId, $receiverId, $token, $uid, $conversationId, $callDetailId)
    {
        $this->channelName = $channelName;
        $this->callerId = $callerId;
        $this->receiverId = $receiverId;
        $this->token = $token;
        $this->uid = $uid;
        $this->conversationId = $conversationId;
        $this->callDetailId = $callDetailId;
    }


    public function broadcastOn()
    {
        return new PrivateChannel('call.' . $this->receiverId);
    }

    public function broadcastAs()
    {
        return 'incoming-voice-call';
    }

    public function broadcastWith()
    {
        return [
            'channel_name' => $this->channelName,
            'caller_id' => $this->callerId,
            'token' => $this->token,
            'uid' => $this->uid,
            'conversation_id' => $this->conversationId,
            'call_type' => 'voice',
            'call_detail_id' => $this->callDetailId
        ];
    }
}
