<?php

namespace App\Http\Resources\User\Chat;

use Carbon\Carbon;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ConversationMessagesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'initiator_id' => $this->initiator_id,
            'receiver_id' => (int) $this->receiver_id,
            'updated_at' => Carbon::parse($this->updated_at)->diffForHumans(),
            'status' => Auth::id() == $this->initiator_id ? $this->status_by_initiator : $this->status_by_receiver,
            'unread_message_count' => isset($this->unread_message_count) ? $this->unread_message_count : 0,
            'conversation_messages' => !empty($this->messages) ? $this->messages->map(function ($messages, $date) {
                return [
                    'date' => $date,
                    'messages' => MessageResource::collection($messages),
                ];
            })->values()->toArray() : [],
        ];
    }
}
