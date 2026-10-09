<?php

namespace Modules\Services\Http\Resources\Provider;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Http\Resources\Concerns\FormatsMilestoneOrderPhases;
use Modules\Services\Http\Resources\User\ServiceOfferOrderRevisionResource;
use Modules\Services\Utilities\ServiceFormatHelper;

class ServiceOfferOrderDetailsResource extends JsonResource
{
    use FormatsMilestoneOrderPhases;

    private function getTimeRemaining(): array
    {
        if ($this->isMilestoneOrder() && $this->relationLoaded('currentPhase') && $this->currentPhase) {
            $phase = $this->currentPhase;
            if (! $phase->started_at) {
                return [
                    'total_minutes' => null,
                    'formatted' => 'Not started',
                    'is_overdue' => false,
                    'started' => false,
                ];
            }
            $deadline = $phase->deadline ?? $phase->started_at->copy()->addDays($phase->duration_days);
            $diffInMinutes = now()->diffInMinutes($deadline, false);
            if ($diffInMinutes <= 0) {
                return [
                    'total_minutes' => 0,
                    'formatted' => 'Expired',
                    'is_overdue' => true,
                    'started' => true,
                    'deadline' => ServiceFormatHelper::apiDateTime($deadline),
                ];
            }

            return [
                'total_minutes' => $diffInMinutes,
                'formatted' => ServiceFormatHelper::countdownFromMinutes($diffInMinutes),
                'is_overdue' => false,
                'started' => true,
                'deadline' => ServiceFormatHelper::apiDateTime($deadline),
            ];
        }

        if (!$this->started_at) {
            return [
                'total_minutes' => null,
                'formatted' => 'Not started',
                'is_overdue' => false,
                'started' => false,

            ];
        }

        $deliveryMinutes = $this->effectiveDeliveryMinutes();

        if ($deliveryMinutes <= 0) {
            return [
                'total_minutes' => 0,
                'formatted' => '0 min',
                'is_overdue' => false,
                'started' => true,
            ];
        }

        $deadline = $this->deadline ?? $this->started_at->copy()->addMinutes($deliveryMinutes);
        $now = now();

        $diffInMinutes = $now->diffInMinutes($deadline, false);

        if ($diffInMinutes <= 0) {
            return [
                'total_minutes' => 0,
                'formatted' => 'Expired',
                'is_overdue' => true,
                'started' => true,
            ];
        }

        $days = intdiv($diffInMinutes, 1440);
        $hours = intdiv($diffInMinutes % 1440, 60);
        $minutes = $diffInMinutes % 60;

        return [
            'total_minutes' => $diffInMinutes,
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'formatted' => ServiceFormatHelper::countdownFromMinutes($diffInMinutes),
            'is_overdue' => false,
            'started' => true,
            'deadline' => ServiceFormatHelper::apiDateTime($deadline),
        ];
    }

    public function toArray(Request $request): array
    {
        $provider = $this->provider;
        $offer = $this->offer();
        $buyer = $this->buyer;
        $escrow = $this->latestEscrow;
        $orderableType = $this->orderableType();
        $milestonePayload = $this->milestonePhasePayload($this->resource, 'provider');

        $paymentStatus = $milestonePayload['payment_status'] ?? match ($escrow?->status?->value ?? $escrow?->status) {
            'PENDING' => 'pending',
            'PAID' => 'in_escrow',
            'RELEASED' => 'paid',
            'FAILED' => 'failed',
            default => 'unpaid',
        };

        $avgRating = $offer?->reviews_avg_rating;
        $reviewsCount = $offer?->reviews_count;

        $servicePrice = $this->effectivePrice();
        $vat = round($servicePrice * 0.02, 2);
        $fees = 0;
        $total = $servicePrice + $vat + $fees;
        $timeRemaining = $this->getTimeRemaining();

        $revisions = $this->revisions ?? collect();
        $revisionsAllowed = $this->allowedRevisions();
        $revisionsUsed = $revisions->filter(
            fn($rev) => $rev->status !== ServiceOfferOrderRevisionStatus::Rejected
        )->count();

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'orderable_type' => $orderableType?->value,
            'orderable_id' => $this->effectiveOrderableId(),
            'reference_service_offer_id' => $this->when(
                $orderableType === OrderableType::MilestoneTemplate,
                $this->reference_service_offer_id
            ),

            'status' => $this->status?->value ?? $this->status,
            'payment_status' => $paymentStatus,

            'purchased_on' => ServiceFormatHelper::apiDateTime($this->created_at),

            'delivery_time' => [
                'minutes' => $this->delivery_time_minutes,
                'formatted' => ServiceFormatHelper::durationFromMinutesShort($this->delivery_time_minutes),
            ],

            'time_remaining' => $timeRemaining,
            'deadline' => $this->deadline
                ? ServiceFormatHelper::apiDateTime($this->deadline)
                : null,

            // `milestone` is a frozen snapshot of the milestone header taken at
            // define time — NOT the live template, which the provider can edit
            // after the order is placed. Totals are derived from the phases, so
            // they can't drift. It carries NO phase list: `phases` below is the
            // single source, so the response never has two divergent phase lists.
            'milestone' => $milestonePayload['milestone'],
            'phases' => $milestonePayload['phases'],
            'cursor' => $milestonePayload['cursor'],

            'service' => in_array($orderableType, [OrderableType::ServiceOffer, OrderableType::MilestoneTemplate], true) ? [
                'id' => $offer?->id,
                'title' => $offer?->title ?? $this->title,
                'description' => $offer?->description,
                'cover' => $offer?->coverMedia
                    ? FileManager::getUrl($offer->coverMedia->media_path)
                    : null,
                'category' => $offer?->serviceCategory?->name ?? null,

                'rating' => $avgRating ? round($avgRating, 1) : 0,
                'reviews_count' => $reviewsCount ?? 0,

                'type' => $offer?->type?->value ?? $offer?->type,
                'delivery_model' => $offer?->delivery_model?->value ?? $offer?->delivery_model,
            ] : null,

            'customer' => [
                'id' => $buyer?->id,
                'name' => $buyer?->name,
                'avatar' => $buyer?->photo ? url($buyer->photo) : null,
                'country' => $buyer?->country_code,
                'region' => $buyer?->region,
            ],

            'requirements' => [
                'notes' => $this->requirements['notes'] ?? null,
                'attachments' => collect($this->requirements['attachments'] ?? [])
                    ->map(fn($file) => FileManager::getUrl($file))
                    ->values(),
            ],

            'deliveries' => $this->deliveries->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'version' => $delivery->version,
                    'message' => $delivery->message,
                    'attachments' => collect($delivery->attachments ?? [])
                        ->map(fn($file) => FileManager::getUrl($file))
                        ->values(),
                    'submitted_at' => ServiceFormatHelper::apiDateTime($delivery->created_at),
                ];
            }),

            'revision_policy' => [
                'revisions_allowed' => $revisionsAllowed,
                'revisions_used' => $revisionsUsed,
                'revisions_remaining' => max(0, $revisionsAllowed - $revisionsUsed),
            ],

            'revisions' => ServiceOfferOrderRevisionResource::collection($this->revisions ?? []),

            'summary' => [
                'service_price' => $servicePrice,
                'vat' => $vat,
                'fees' => $fees,
                'total' => $total,
                'currency' => $this->currency,
            ],

            'provider' => [
                'id' => $provider?->id,
                'name' => $provider?->display_name,
                'avatar' => $provider?->user?->photo ? url($provider->user->photo) : null,
                'type' => $provider?->type,
                'country' => $provider?->country_code,
                'region' => $provider?->region,
            ],

            'rejection' => $this->status === ServiceOfferOrderStatus::Rejected ? [
                'reason' => $this->rejectionReason ? [
                    'id' => $this->rejectionReason->id,
                    'name' => $this->rejectionReason->name,
                ] : null,
                'notes' => $this->rejection_notes,
            ] : null,

            'extensions' => ServiceOfferOrderExtensionResource::collection(
                $this->whenLoaded('extensions')
            ),
        ];
    }
}
