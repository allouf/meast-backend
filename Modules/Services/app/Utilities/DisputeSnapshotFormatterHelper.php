<?php

namespace Modules\Services\Utilities;

use App\Utilities\FileManager;
use Modules\Services\Enums\ServiceOfferOrderDisputeEvidenceType;

final class DisputeSnapshotFormatterHelper
{
    public static function format(
        ServiceOfferOrderDisputeEvidenceType $type,
        array $snapshot
    ): array {
        return match ($type) {
            ServiceOfferOrderDisputeEvidenceType::Revision =>
                self::formatRevision($snapshot),

            ServiceOfferOrderDisputeEvidenceType::Extension =>
                self::formatExtension($snapshot),

            ServiceOfferOrderDisputeEvidenceType::Delivery =>
                self::formatDelivery($snapshot),

            default => $snapshot,
        };
    }

    /* ============================= */

    private static function formatRevision(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'delivery_version' => $data['delivery_version'] ?? null,
            'notes' => $data['notes'] ?? null,
            'request_type' => $data['request_type'] ?? null,
            'status' => $data['status'] ?? null,
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'attachments' => collect($data['attachments'] ?? [])
                ->map(fn ($file) => FileManager::getUrl($file))
                ->values(),
            'requested_at' => self::date($data['requested_at'] ?? null),
            'responded_at' => self::date($data['responded_at'] ?? null),
        ];
    }

    private static function formatExtension(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'requested_days' => $data['requested_days'] ?? null,

            'reason' => [
                'id' => $data['reason']['id'] ?? null,
                'name' => $data['reason']['name'] ?? null,
            ],

            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? null,

            'requested_at' => self::date($data['created_at'] ?? null),
            'responded_at' => self::date($data['responded_at'] ?? null),
        ];
    }

    private static function formatDelivery(array $data): array
    {
        return [
            'id' => $data['id'] ?? null,
            'version' => $data['version'] ?? null,
            'message' => $data['message'] ?? null,
            'attachments' => collect($data['attachments'] ?? [])
                ->map(fn($file) => FileManager::getUrl($file))
                ->values(),
            'submitted_at' => self::date($data['submitted_at'] ?? null),
        ];
    }

    /* ============================= */

    private static function date($value): ?string
    {
        if (!$value) return null;

        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Exception $e) {
            return $value;
        }
    }
}