<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'sender_id'    => $this->sender_id,
            // 'sender_role'  => $this->sender_role,
            'message'      => $this->message,
            'attachments'  => $this->attachments ?? [],
            'created_at'   => $this->created_at?->toDateTimeString(),
        ];

    }
}
