<?php

namespace Modules\Advertisement\Http\Resources\Admin;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementCategoryResource extends JsonResource
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
            'icon' => $this->icon ? FileManager::getUrl($this->icon) : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'subcategories_count' => $this->subcategories_count ?? 0,
            'advertisements_count' => $this->advertisements_count ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
