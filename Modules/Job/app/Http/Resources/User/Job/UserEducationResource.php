<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserEducationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'degree_type' => $this->degree_type,
            'university_name' => $this->university_name,
            'major' => $this->major,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'expected_graduation_date' => $this->expected_graduation_date?->toDateString(),
            'is_current' => (bool) $this->is_current,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

