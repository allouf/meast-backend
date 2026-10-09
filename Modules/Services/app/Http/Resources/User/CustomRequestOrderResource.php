<?php

namespace Modules\Services\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomRequestOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quote = $this->relationLoaded('quote') ? $this->quote : null;
        $rfq = $quote?->relationLoaded('customRequest') ? $quote->customRequest : null;
        $provider = $this->relationLoaded('provider') ? $this->provider : null;
        $providerUser = $provider?->relationLoaded('user') ? $provider->user : null;

        return [
            'id' => $this->id,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status?->value ?? $this->payment_status,
            'service_price' => (float) $this->service_price,
            'vat_amount' => (float) $this->vat_amount,
            'fees_amount' => (float) $this->fees_amount,
            'total_amount' => (float) $this->total_amount,
            'quote' => $quote ? [
                'id' => $quote->id,
                'proposed_budget' => (float) $quote->proposed_budget,
                'estimated_delivery_date' => $quote->estimated_delivery_date?->format('Y-m-d'),
                'status' => $quote->status?->value ?? $quote->status,
            ] : null,
            'custom_request' => $rfq ? [
                'id' => $rfq->id,
                'reference_number' => $rfq->reference_number,
                'title' => $rfq->title,
            ] : null,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'photo' => $this->client->photo ? FileManager::getUrl($this->client->photo) : null,
            ]),
            'provider' => $this->when($provider, fn () => [
                'id' => $provider->id,
                'service_provider_id' => $provider->id,
                'user_id' => $provider->user_id,
                'name' => $provider->display_name ?? $providerUser?->name,
                'photo' => $providerUser?->photo ? FileManager::getUrl($providerUser->photo) : null,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
