<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class JobProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gallery = $this->gallery ?? [];
        $galleryUrls = array_map(fn ($path) => FileManager::getUrl($path), $gallery);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'bio' => $this->bio,
            'avatar' => $this->avatar ? FileManager::getUrl($this->avatar) : null,
            'cover' => $this->cover ? FileManager::getUrl($this->cover) : null,
            'gallery' => $galleryUrls,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
