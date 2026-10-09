<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        return [
            'id' => $this->id,
            'name' => (string) ($this->getTranslation('name', $locale) ?? $this->getTranslation('name', config('app.fallback_locale')) ?? ''),
            'slug' => $this->slug,
        ];
    }
}
