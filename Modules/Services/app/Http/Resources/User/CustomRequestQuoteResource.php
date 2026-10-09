<?php

namespace Modules\Services\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomRequestQuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $provider = $this->relationLoaded('provider') ? $this->provider : null;
        $user = $provider?->relationLoaded('user') ? $provider->user : null;

        return [
            'id' => $this->id,
            'proposed_budget' => (float) $this->proposed_budget,
            'estimated_delivery_date' => $this->estimated_delivery_date?->format('Y-m-d'),
            'cover_letter_or_message' => $this->cover_letter_or_message,
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'provider' => [
                'id' => $provider?->id,
                'service_provider_id' => $provider?->id,
                'user_id' => $provider?->user_id,
                'name' => $provider?->display_name ?? $user?->name,
                'photo' => $user?->photo ? FileManager::getUrl($user->photo) : null,
                'country_code' => $provider?->country_code ?? $user?->country_code,
                'provider_type' => $provider?->type,
            ],
        ];
    }
}
