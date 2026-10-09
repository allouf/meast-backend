<?php

namespace Modules\Community\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReactionResource extends JsonResource
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
            'type' => $this->type,
            'type_emoji' => $this->type_emoji,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'photo' => $this->user->photo,
                'type' => $this->user->type,
            ],
            'reactionable' => $this->when($this->reactionable, function () {
                return [
                    'id' => $this->reactionable->id,
                    'type' => class_basename($this->reactionable),
                    'title' => $this->reactionable->caption ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
