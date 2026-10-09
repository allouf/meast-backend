<?php

namespace Modules\Store\Http\Resources\Admin;

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
            'id' =>$this->id,
            'name' => [
                'en' =>$this->getTranslation('name', 'en'),
                'ar' =>$this->getTranslation('name', 'ar'),
            ],
            'icon' =>$this->icon ? FileManager::getUrl($this->icon) : null,
            'is_active' =>$this->is_active,
            'parent_id' =>$this->parent_id,
            'sort_order' =>$this->sort_order,
            'children' =>CategoryResource::collection($this->whenLoaded('allChildren')),
        ];
    }
}
