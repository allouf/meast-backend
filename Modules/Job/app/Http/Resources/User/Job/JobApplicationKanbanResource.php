<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationKanbanResource extends JsonResource
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

            'candidate' => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'avatar' => $this->candidate->photo,
            ],

            'rating' => $this->rating,

            'status' => $this->status->value,

            'created_at' => $this->created_at?->diffForHumans(),

            'tags' => $this->whenLoaded(
                'tags',
                fn() =>
                $this->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])
            ),
        ];
    }
}
