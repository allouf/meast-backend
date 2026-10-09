<?php

namespace Modules\Services\Enums;

enum ProviderVerificationStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }
}
