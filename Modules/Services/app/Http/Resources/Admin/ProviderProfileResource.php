<?php

namespace Modules\Services\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Http\Resources\Provider\ServiceOfferResource;
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
            'verification_status' => $this->verification_status?->value
                ?? $this->verification_status
                ?? 'none',
            'verification_notes' => $this->verification_notes,
            'is_verified' => (bool) $this->is_verified,
            'languages' => $this->whenLoaded('languages', function () {
                $locale = app()->getLocale();
                return $this->languages->map(fn($lang) => [
                    'id' => $lang->id,
                    'code' => $lang->code,
                    'name' => (string) ($lang->getNameForLocale($locale) ?? $lang->getNameForLocale(config('app.fallback_locale')) ?? ''),
                ])->values()->all();
            }),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name ?? null,
                'email' => $this->user->email ?? null,
                'photo' => $this->user->photo ? FileManager::getUrl($this->user->photo) : null,
            ]),
            'service_offers_count' => $this->when(isset($this->service_offers_count), fn() => $this->service_offers_count),
            'service_offers' => $this->whenLoaded('serviceOffers', fn() => ServiceOfferResource::collection($this->serviceOffers)),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
