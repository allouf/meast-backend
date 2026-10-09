<?php

namespace Modules\Services\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        return [
            'id' => $this->id,
            'name' => (string) ($this->getTranslation('name', $locale) ?? $this->getTranslation('name', config('app.fallback_locale')) ?? ''),
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'requires_verification' => (bool) $this->requires_verification,
            'boost_prohibited' => (bool) $this->boost_prohibited,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
