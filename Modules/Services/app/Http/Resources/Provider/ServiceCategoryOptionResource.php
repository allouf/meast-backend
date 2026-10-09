<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryOptionResource extends JsonResource
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
            'requires_verification' => (bool) $this->requires_verification,
        ];
    }
}
