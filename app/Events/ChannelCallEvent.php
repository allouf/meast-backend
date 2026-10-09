<?php

namespace App\Events;

use App\Models\ChannelCallDetail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChannelCallEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callDetail;
    public $action;

    public function __construct(ChannelCallDetail $callDetail, $action)
    {
        $this->callDetail = $callDetail;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel.' . $this->callDetail->channel_id);
    }

    public function broadcastAs()
    {
        return 'channel-call-' . $this->action;
    }

    public function broadcastWith()
    {
        return [
            'call_detail' => [
                'id' => $this->callDetail->id,
                'channel_id' => $this->callDetail->channel_id,
                'initiator' => [
                    'id' => $this->callDetail->initiator->id,
                    'name' => $this->callDetail->initiator->name
                ],
                'call_type' => $this->callDetail->call_type,
                'channel_name' => $this->callDetail->channel_name,
                'participants' => $this->callDetail->participants,
                'start_call_date' => $this->callDetail->start_call_date->format('Y-m-d H:i:s'),
                'is_active' => $this->callDetail->is_active
            ],
            'action' => $this->action
        ];
    }
}
