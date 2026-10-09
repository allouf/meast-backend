<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ProviderProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'display_name' => $this->display_name,
            'logo' => $this->logo ? FileManager::getUrl($this->logo) : null,
            'cover' => $this->cover ? FileManager::getUrl($this->cover) : null,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'bio' => $this->bio,
            'identity_document' => $this->identity_document ? FileManager::getUrl($this->identity_document) : null,
            'verification_notes' => $this->verification_notes,
            'verification_status' => $this->verification_status?->value
                ?? $this->verification_status
                ?? 'none',
            'languages' => $this->whenLoaded('languages', function () {
                $locale = app()->getLocale();
                return $this->languages->map(fn($lang) => [
                    'id' => $lang->id,
                    'code' => $lang->code,
                    'name' => (string) ($lang->getNameForLocale($locale) ?? $lang->getNameForLocale(config('app.fallback_locale')) ?? ''),
                ]);
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
