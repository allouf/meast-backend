<?php

namespace App\Http\Resources\Admin\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        'id'     =>$this->id,
        'title'  => [
            'en' =>$this->getTranslation('title','en'),
            'ar' =>$this->getTranslation('title','ar'),
        ],
        'link'   =>$this->link,
        ];
    }
}
