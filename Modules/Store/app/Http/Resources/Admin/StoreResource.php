<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class StoreResource extends JsonResource
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
            'user_id' => $this->user_id,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],

            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => $this->logo ? FileManager::getUrl($this->logo) : null,
            'cover' => $this->cover ? FileManager::getUrl($this->cover) : null,
            'description' => $this->description,
            'social_links' => $this->social_links ?? [],
            'phones' => $this->phones ?? [],
            'emails' => $this->emails ?? [],
            'address' => $this->address,
            'city' => $this->city,
            'country_code' => $this->country_code,
            'postal_code' => $this->postal_code,
            'region' => $this->region,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'website' => $this->website,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'is_returnable' => (bool) $this->is_returnable,
            'is_freezone' => (bool) $this->is_freezone,
            'is_active' => (bool) $this->is_active,
            'status' => $this->status?->value,
            'status_reason' => $this->status_reason,
            'rating' => (float) $this->rating,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
