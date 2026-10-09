<?php

namespace Modules\Community\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'content' => $this->content,
            'status' => $this->status,
            'is_reply' => $this->is_reply,
            'replies_count' => $this->replies_count,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'photo' => $this->user->photo,
                'type' => $this->user->type,
            ],
            'commentable' => $this->when($this->commentable, function () {
                return [
                    'id' => $this->commentable->id,
                    'type' => strtolower(class_basename($this->commentable)),
                    'title' => $this->commentable->caption ?? null,
                ];
            }),
            'parent' => $this->when($this->parent, function () {
                return [
                    'id' => $this->parent->id,
                    'content' => $this->parent->content,
                    'user' => [
                        'id' => $this->parent->user->id,
                        'name' => $this->parent->user->name,
                    ],
                ];
            }),
            'replies' => $this->when($this->replies, function () {
                return CommentResource::collection($this->replies);
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
