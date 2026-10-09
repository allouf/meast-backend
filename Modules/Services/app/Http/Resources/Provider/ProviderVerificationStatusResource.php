<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\ProviderVerificationStatus;

class ProviderVerificationStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->verification_status instanceof ProviderVerificationStatus
            ? $this->verification_status
            : ProviderVerificationStatus::tryFrom((string) ($this->verification_status ?? 'none'))
                ?? ProviderVerificationStatus::None;

        return [
            'provider_id' => $this->id,
            'verification_status' => $status->value,
            'is_verified' => (bool) $this->is_verified,
            'message' => $this->statusMessage($status),
            'rejection_reason' => $status === ProviderVerificationStatus::Rejected
                ? $this->verification_notes
                : null,
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function statusMessage(ProviderVerificationStatus $status): string
    {
        if ($status === ProviderVerificationStatus::Rejected && blank($this->verification_notes)) {
            return __('services::messages.service_provider.verification_status_rejected_no_reason');
        }

        return match ($status) {
            ProviderVerificationStatus::Pending => __('services::messages.service_provider.verification_status_pending'),
            ProviderVerificationStatus::Approved => __('services::messages.service_provider.verification_status_approved'),
            ProviderVerificationStatus::Rejected => __('services::messages.service_provider.verification_status_rejected'),
            default => __('services::messages.service_provider.verification_status_none'),
        };
    }
}
