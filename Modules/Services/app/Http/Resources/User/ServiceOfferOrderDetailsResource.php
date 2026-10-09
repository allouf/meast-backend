<?php

namespace Modules\Services\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Http\Resources\Concerns\FormatsMilestoneOrderPhases;
use Modules\Services\Utilities\ServiceFormatHelper;

class ServiceOfferOrderDetailsResource extends JsonResource
{
    use FormatsMilestoneOrderPhases;

    public function toArray(Request $request): array
    {
        $provider = $this->provider;
        $offer = $this->offer();
        $escrow = $this->latestEscrow;
        $orderableType = $this->orderableType();
        $milestonePayload = $this->milestonePhasePayload($this->resource, 'buyer');
        $deliveryMinutes = $this->effectiveDeliveryMinutes();

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

        $revisions = $this->revisions ?? collect();
        $plan = $this->selectedPlan();
        $revisionsAllowed = $this->allowedRevisions();
        $revisionsUsed = $revisions->filter(
            fn ($rev) => $rev->status !== ServiceOfferOrderRevisionStatus::Rejected
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
            'price' => $servicePrice,
            'currency' => $this->currency,
            'delivery_time' => [
                'minutes' => $deliveryMinutes,
                'formatted' => ServiceFormatHelper::durationFromMinutesDetailed($deliveryMinutes),
            ],
            'deadline' => $this->deadline
                ? ServiceFormatHelper::apiDateTime($this->deadline)
                : null,
            'purchased_on' => ServiceFormatHelper::apiDateTime($this->created_at),

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
                'estimated_delivery_days' => ServiceFormatHelper::durationFromMinutesDetailed($deliveryMinutes),
            ] : null,

            // `milestone` is a frozen snapshot of the milestone header taken at
            // define time — NOT the live template, which the provider can edit
            // after the order is placed. Totals are derived from the phases, so
            // they can't drift. It carries NO phase list: `phases` below is the
            // single source, so the response never has two divergent phase lists.
            'milestone' => $milestonePayload['milestone'],
            'phases' => $milestonePayload['phases'],
            'cursor' => $milestonePayload['cursor'],

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

            'requirements' => [
                'notes' => $this->requirements['notes'] ?? null,
                'attachments' => collect($this->requirements['attachments'] ?? [])
                    ->map(fn ($file) => FileManager::getUrl($file))
                    ->values(),
                'plan_id' => $this->requirements['plan_id'] ?? null,
            ],

            'deliveries' => $this->deliveries->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'version' => $delivery->version,
                    'message' => $delivery->message,
                    'attachments' => collect($delivery->attachments ?? [])
                        ->map(fn ($file) => FileManager::getUrl($file))
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

            'rejection' => $this->status === ServiceOfferOrderStatus::Rejected ? [
                'reason' => $this->rejection_reason,
                'notes' => $this->rejection_notes,
            ] : null,

            'extensions' => ServiceOfferOrderExtensionResource::collection(
                $this->whenLoaded('extensions')
            ),
        ];
    }
}
