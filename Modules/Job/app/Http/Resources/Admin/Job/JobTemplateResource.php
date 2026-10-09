<?php

namespace Modules\Job\Http\Resources\Admin\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'is_system_template' => $this->is_system_template,
            'is_active' => $this->is_active,
            'jobs_count' => (int) ($this->jobs_count ?? 0),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'employer' => $this->whenLoaded('employer', fn () => $this->employer ? [
                'id' => $this->employer->id,
                'name' => $this->employer->name ?? null,
                'email' => $this->employer->email ?? null,
            ] : null),
            'fields' => JobTemplateFieldResource::collection($this->whenLoaded('fields')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
