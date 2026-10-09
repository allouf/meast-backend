<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlaRuleResource extends JsonResource
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
            'store_id' => $this->store_id,
            'issue_type' => new IssueTypeResource($this->issueType),
            'sla_response_hours' => $this->sla_response_hours
        ];

    }
}
