<?php

namespace Modules\Community\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReelResource extends JsonResource
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
            'video' => $this->video_url,
            'caption' => $this->caption,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'photo' => $this->user->photo,
                'type' => $this->user->type,
            ] : null,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'reactions_count' => $this->reactions_count ?? $this->reactions()->count(),
            'comments_count' => $this->comments_count ?? $this->allComments()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
