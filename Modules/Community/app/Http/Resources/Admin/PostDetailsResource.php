<?php

namespace Modules\Community\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostDetailsResource extends JsonResource
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
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'photo' => $this->user->photo,
                'type' => $this->user->type,
            ] : null,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'type' => $this->type,
            'reports_count' => $this->reports_count,
            'reactions_count' => $this->reactions_count ?? $this->reactions()->count(),
            'comments_count' => $this->comments_count ?? $this->allComments()->count(),
            'created_at' => Carbon::parse($this->created_at)->toDateTimeString(),
            'updated_at' => Carbon::parse($this->updated_at)->toDateTimeString(),
            'logs' => $this->whenLoaded('logs', function () {
                return PostLogResource::collection($this->logs);
            })
        ];
    }
}
