<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'gender' => $this->gender,
            'birthday' => $this->birthday,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'photo' => $this->photo ? FileManager::getUrl($this->photo) : null,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'posts_count' => $this->posts()->count(),
        ];
    }
}
