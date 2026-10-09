<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ServiceOfferMediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->media_type,
            'url' => FileManager::getUrl($this->media_path),
            'is_cover' => $this->is_cover,
            'sort_order' => $this->sort_order,
        ];
    }
}
