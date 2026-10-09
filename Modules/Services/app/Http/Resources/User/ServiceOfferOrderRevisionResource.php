<?php

namespace Modules\Services\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Utilities\ServiceFormatHelper;

class ServiceOfferOrderRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_version' => $this->delivery_version,
            'notes' => $this->notes,
            'request_type' => $this->request_type instanceof \BackedEnum
                ? $this->request_type->value
                : $this->request_type,
            'status' => $this->status instanceof \BackedEnum
                ? $this->status->value
                : $this->status,
            'rejection_reason' => $this->rejection_reason,
            'attachments' => collect($this->attachments ?? [])
                ->map(fn ($file) => FileManager::getUrl($file))
                ->values(),
            'requested_at' => ServiceFormatHelper::apiDateTime($this->requested_at),
            'responded_at' => ServiceFormatHelper::apiDateTime($this->responded_at),
        ];
    }
}
