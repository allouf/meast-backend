<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            // 'store_id'    => $this->store_id,
            'name'        => $this->name,
            'description' => $this->description,
            'priority'    => $this->priority,

            // 'sla_rule'    => $this->whenLoaded('slaRule', function () {
            //     return [
            //         'id' => $this->slaRule->id,
            //         'sla_response_hours' => $this->slaRule->sla_response_hours,
            //     ];
            // }),

            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];

    }
}
