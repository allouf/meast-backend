<?php

namespace Modules\Job\Http\Resources\Admin\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'jobs_count' => $this->jobs_count ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
