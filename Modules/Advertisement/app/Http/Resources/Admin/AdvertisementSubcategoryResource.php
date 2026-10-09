<?php

namespace Modules\Advertisement\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementSubcategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAllRoute = $request->route()?->getActionMethod() === 'all';
        return [
            'id' => $this->id,
            ...($isAllRoute ? [
                'name_en' => $this->getTranslation('name', 'en'),
                'name_ar' => $this->getTranslation('name', 'ar'),
            ] : ['name' => $this->name]),
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'category' => $this->whenLoaded('category', function () use ($isAllRoute) {
                return [
                    'id' => $this->category->id,
                    ...($isAllRoute ? [
                        'name_en' => $this->category->getTranslation('name', 'en'),
                        'name_ar' => $this->category->getTranslation('name', 'ar'),
                    ] : ['name' => $this->category->name]),
                    'slug' => $this->category->slug,
                ];
            }),
            'advertisements_count' => $this->advertisements_count ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
