<?php

namespace App\Events;

use App\Models\ChannelMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChannelMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(ChannelMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel.' . $this->message->channel_id);
    }

    public function broadcastAs()
    {
        return 'new-channel-message';
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'channel_id' => $this->message->channel_id,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                    'photo' => $this->message->sender->photo
                ],
                'message' => $this->message->message,
                'message_type' => $this->message->message_type,
                'is_pinned' => $this->message->is_pinned,
                'reply_to' => $this->message->replyTo ? [
                    'id' => $this->message->replyTo->id,
                    'message' => $this->message->replyTo->message,
                    'sender_name' => $this->message->replyTo->sender->name
                ] : null,
                'created_at' => $this->message->created_at->format('Y-m-d H:i:s')
            ]
        ];
    }
}

