<?php

namespace Modules\Services\Http\Resources\Provider;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\ServiceOfferOrderDisputeStatus;

class DisputeResource extends JsonResource
{
    public function toArray($request): array
    {
        $order = $this->order;

        return [
            'id' => $this->id,

            // =====================
            // Dispute Info
            // =====================
            'status' => $this->status->value,
            'opened_by' => $this->opened_by,
            'description' => $this->description,
            'requested_on' => optional($this->created_at)->format('M d, Y'),
            'frozen_at' => $this->frozen_at,
            'resolved_at' => $this->resolved_at,

            // =====================
            // Order Snapshot
            // =====================
            'order' => $order ? [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'price' => $order->price,
                'currency' => $order->currency,
                'category' => ($offer = $order->offer()) && $offer->serviceCategory
                    ? new ServiceCategoryOptionResource($offer->serviceCategory)
                    : null,
                'requirements' => [
                    'notes' => $order->requirements['notes'] ?? null,
                    'attachments' => collect($order->requirements['attachments'] ?? [])
                        ->map(fn($file) => FileManager::getUrl($file))
                        ->values(),
                ],
                'status' => $order->status->value,
                'deadline' => optional($order->deadline)?->toDateTimeString(),
            ] : null,

            // =====================
            // Participants
            // =====================
            'buyer' => [
                'id' => $this->buyer?->id,
                'name' => $this->buyer?->name,
                'email' => $this->buyer?->email,
            ],

            'provider' => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->display_name,
                'user_id' => $this->provider?->user_id,
            ],

            // =====================
            // Reason
            // =====================
            'reason' => [
                'id' => $this->reason?->id,
                'name' => $this->reason?->name,
                'validation_type' => $this->reason?->validation_type,
            ],

            'can_reply' => in_array($this->status, [
                ServiceOfferOrderDisputeStatus::Open,
                ServiceOfferOrderDisputeStatus::UnderReview,
            ]),
            // =====================
            // Evidence Timeline
            // =====================
            'evidences' => DisputeEvidenceResource::collection($this->whenLoaded('evidences')),
        ];
    }
}
