<?php

namespace App\Http\Resources\User\Chat;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'conversation_id'=>$this->conversation_id,
            'sender_id'=>$this->sender_id,
            'message'=> $this->message_type != 'text' ? FileManager::getUrl($this->message) : $this->message,
            'message_type'=> $this->message_type,
            'created_at' => Carbon::parse($this->created_at)->format('g:i A'),
            'is_read'=>$this->is_read,
        ];
    }
}
