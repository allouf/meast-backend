<?php

namespace Modules\Services\Http\Resources\Provider;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Utilities\ServiceFormatHelper;

class ServiceOfferOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $provider = $this->relationLoaded('provider') ? $this->provider : null;
        $buyer = $this->relationLoaded('buyer') ? $this->buyer : null;
        $offer = $this->offer();
        $deliveryMinutes = $this->effectiveDeliveryMinutes();

        $escrow = $this->whenLoaded('latestEscrow');

        $paymentStatus = match ($escrow?->status?->value ?? $escrow?->status) {
            'PENDING' => 'pending',
            'PAID' => 'in_escrow',
            'RELEASED' => 'paid',
            'FAILED' => 'failed',
            default => 'unpaid',
        };

        return [

            'id' => $this->id,
            'order_number' => $this->order_number,
            // Mirrors the User-side list resource: surface orderable_type so
            // the FE can render badges + route correctly (single-offer detail
            // page vs milestone phases page). For milestone orders include
            // reference_service_offer_id so the provider's UI can link back
            // to the offer the buyer was negotiating against.
            'orderable_type' => $this->orderableType()?->value,
            'reference_service_offer_id' => $this->when(
                $this->isMilestoneOrder(),
                $this->reference_service_offer_id
            ),
            'status' => $this->status?->value ?? $this->status,
            'payment_status' => $paymentStatus,

            'service' => [
                'title' => $offer?->title ?? $this->title,
                'cover' => $offer?->coverMedia
                    ? FileManager::getUrl($offer->coverMedia->media_path)
                    : null,
            ],

            'provider' => [
                'name' => $provider?->display_name ?? '',
                'avatar' => $provider?->user?->photo
                    ? FileManager::getUrl($provider->user->photo)
                    : null,
                'type' => $provider?->type ?? '',
            ],

            'customer' => [
                'id' => $buyer?->id,
                'name' => $buyer?->name,
                'avatar' => $buyer?->photo
                    ? FileManager::getUrl($buyer->photo)
                    : null,
                'country' => $buyer?->country_code,
            ],

            'order_date' => ServiceFormatHelper::apiDateTime($this->created_at),

            'delivery_time' => [
                'minutes' => $deliveryMinutes,
                'formatted' => ServiceFormatHelper::durationFromMinutesCompact($deliveryMinutes),
            ],

            'total' => $this->effectivePrice(),
            'currency' => $this->currency,

        ];
    }
}
