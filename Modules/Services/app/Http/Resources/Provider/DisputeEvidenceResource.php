<?php

namespace Modules\Services\Http\Resources\Provider;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\ServiceOfferOrderDisputeEvidenceType;
use Modules\Services\Http\Resources\User\ServiceOfferOrderRevisionResource;
use Modules\Services\Utilities\DisputeSnapshotFormatterHelper;

class DisputeEvidenceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,

            'reference_id' => $this->reference_id,

            // =========================
            // Normalized snapshot
            // =========================
            'data' => DisputeSnapshotFormatterHelper::format(
                $this->type,
                $this->data_snapshot
            ),
            'label' => $this->resolveLabel(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    private function resolveLabel(): string
    {
        return match ($this->type) {
            ServiceOfferOrderDisputeEvidenceType::Revision => 'Revision Request',
            ServiceOfferOrderDisputeEvidenceType::Extension => 'Extension Request',
            ServiceOfferOrderDisputeEvidenceType::Delivery => 'Delivery Submission',
            default => 'Evidence',
        };
    }
}
