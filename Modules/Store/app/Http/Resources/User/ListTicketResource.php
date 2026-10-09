<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ListTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'store_id'  => $this->store_id,
            'user_id'   => $this->user_id,
            'sender' => $this->whenLoaded('user', function () {
                return [
                    'id'    => $this->user->id,
                    'name'  => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'photo' => $this->user->photo ? FileManager::getUrl($this->user->photo) : null,
                ];
            }),
            'issue_type' => new IssueTypeResource($this->whenLoaded('issueType')),
            'priority'   => $this->priority,
            'subject'    => $this->subject,
            'status'     => $this->status,
            'sla_response_hours' => $this->sla_response_hours,
            'sla_due_at'         => $this->sla_due_at?->toDateTimeString(),
            'is_sla_breached'    => $this->is_sla_breached,
            'sla_delay' => $this->sla_delay_formatted,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
