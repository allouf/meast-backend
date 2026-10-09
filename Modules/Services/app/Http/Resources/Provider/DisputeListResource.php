<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeListResource extends JsonResource
{
    public function toArray($request): array
    {
        $order = $this->order;
        $userId = $request->user()->id;

        $isBuyer = $this->buyer_id === $userId;
        $isProvider = $this->provider?->user_id === $userId;

        $openedByMe =
            ($this->opened_by === 'buyer' && $isBuyer) ||
            ($this->opened_by === 'provider' && $isProvider);

        return [
            // =====================
            // Identity
            // =====================
            'id' => $this->id,

            'order_number' => $order?->order_number,

            // =====================
            // Order Core Info
            // =====================
            'title' => $order?->title,

            'deadline' => $order?->deadline?->toDateTimeString(),

            // =====================
            // Provider Info
            // =====================
            'provider' => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->display_name,
            ],


            'buyer' => [
                'id' => $this->buyer?->id,
                'name' => $this->buyer?->name,
                'email' => $this->buyer?->email,
            ],


            // =====================
            // Reason
            // =====================
            'reason' => [
                'id' => $this->reason?->id,
                'name' => $this->reason?->name,
                'validation_type' => $this->reason?->validation_type?->value,
            ],

            // =====================
            // Escrow Snapshot
            // =====================
            'escrow' => [
                'amount' => $order?->latestEscrow?->amount,
                'currency' => $order?->currency,
            ],

            // =====================
            // Dispute State
            // =====================
            'status' => $this->status?->value,

            'opened_by' => $this->opened_by,

            'created_at' => $this->created_at?->toDateTimeString(),

            'opened_by_me' => $openedByMe,
            'opened_against_me' => !$openedByMe,

            'my_role' => $isBuyer ? 'buyer' : ($isProvider ? 'provider' : null),

            // =====================
            // Derived Info (Useful for UI)
            // =====================
            'is_active' => $this->status?->isOpen() ?? false,

            'is_closed' => $this->status?->isClosed() ?? false,
        ];
    }
}
