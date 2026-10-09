<?php

namespace Modules\Services\Enums;

enum ServiceOfferOrderDisputeStatus: string
{
    case Open = 'OPEN';
    case UnderReview = 'UNDER_REVIEW';
    case Resolved = 'RESOLVED';
    case Rejected = 'REJECTED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /*
    |----------------------------------------------------------
    | Helpers 
    |----------------------------------------------------------
    */

    public function isOpen(): bool
    {
        return $this === self::Open || $this === self::UnderReview;
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::Rejected]);
    }
}