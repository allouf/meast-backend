<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class CategoryResource extends JsonResource
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
            'icon' => $this->icon ? FileManager::getUrl($this->icon) : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'children'   => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
