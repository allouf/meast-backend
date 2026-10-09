<?php

namespace Modules\Services\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Utilities\ServiceFormatHelper;

class ServiceOfferOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $provider = $this->relationLoaded('provider') ? $this->provider : null;
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
                'avatar' => $provider?->user?->photo ? url($provider->user->photo) : null,
                'type' => $provider?->type ?? '',
            ],

            'order_date' => ServiceFormatHelper::apiDateTime($this->created_at),

            'delivery_time' => [
                'minutes' => $deliveryMinutes,
                'formatted' => ServiceFormatHelper::durationFromMinutesCompact($deliveryMinutes),
            ],

            'total' => $this->effectivePrice(),
            'currency' => $this->currency,

            'rejection' => $this->status === ServiceOfferOrderStatus::Rejected ? [
                'reason' => $this->rejectionReason?->name,
                'notes' => $this->rejection_notes,
            ] : null,

        ];
    }
}
