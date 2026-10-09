<?php

namespace App\Http\Resources\Admin\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class SliderResource extends JsonResource
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
            'category_id'=>$this->category_id,
            'title' => [
                'en' => $this->getTranslation('title', 'en'),
                'ar' => $this->getTranslation('title', 'ar'),
            ],
            'sub_title' => [
                'en' => $this->getTranslation('sub_title', 'en'),
                'ar' => $this->getTranslation('sub_title', 'ar'),
            ],
            'image' =>$this->image ? FileManager::getUrl($this->image) : null,
        ];
    }
}
