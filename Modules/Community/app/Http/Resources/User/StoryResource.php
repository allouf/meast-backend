<?php

namespace Modules\Community\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class StoryResource extends JsonResource
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
            'media' => $this->media_url,
            'status' => $this->status,
            'type' => $this->type,
            'background_color' => $this->background_color,
            'is_featured' => $this->is_featured,
            'is_expired' => $this->is_expired,
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'is_owner' => $this->user_id == Auth::id(),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'photo' => $this->user->photo,
                'type' => $this->user->type,
            ] : null,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'reactions_count' => $this->reactions_count ?? $this->reactions()->count(),
            'replies_count' => $this->replies_count ?? $this->allReplies()->count(),
            'views_count' => $this->views_count,
            'viewers' => $this->viewers,
            'user_reaction' => $this->user_reaction,
            'reactions_by_type' => $this->reactions_by_type ?? [],
            'total_reactions' => $this->total_reactions ?? 0,
            'total_replies' => $this->total_replies ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
