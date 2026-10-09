<?php

namespace Modules\Community\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


class PostResource extends JsonResource
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
            'image' => $this->image_url,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
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
            'comments_count' => $this->comments_count ?? $this->allComments()->count(),
            'user_reaction' => $this->user_reaction,
            'reactions_by_type' => $this->reactions_by_type ?? [],
            'total_reactions' => $this->total_reactions ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
