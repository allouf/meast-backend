<?php

namespace Modules\Services\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\ProviderVerificationStatus;

class ProviderProfileAboutResource extends JsonResource
{
    /**
     * Public "about" payload for provider profile (customer view).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->relationLoaded('user') ? $this->user : null;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'display_name' => (string) ($this->display_name ?? ''),
            'bio' => (string) ($this->bio ?? ''),
            'avatar' => $user && $user->photo ? FileManager::getUrl($user->photo) : null,
            'cover' => $this->cover ? FileManager::getUrl($this->cover) : null,
            'service_languages' => $this->whenLoaded('languages', function () {
                $locale = app()->getLocale();
                $fallback = config('app.fallback_locale');
                return $this->languages->map(fn ($lang) => [
                    'id' => $lang->id,
                    'code' => $lang->code,
                    'name' => (string) ($lang->getNameForLocale($locale) ?? $lang->getNameForLocale($fallback) ?? ''),
                ])->values()->all();
            }),
            'average_rating' => (float) ($this->getAttribute('average_rating') ?? 0),
            'total_reviews' => (int) ($this->getAttribute('total_reviews') ?? 0),
            'country_code' => (string) ($this->country_code ?? ''),
            'region' => (string) ($this->region ?? ''),
            'is_verified' => ($this->verification_status instanceof ProviderVerificationStatus
                    && $this->verification_status->isApproved())
                || (string) ($this->verification_status ?? '') === ProviderVerificationStatus::Approved->value
                || (bool) ($this->is_verified ?? false),
            'verification_message' => (string) ($this->verification_notes ?? ''),
        ];
    }
}
