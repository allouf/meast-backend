<?php

namespace Modules\Services\Enums;

enum ServiceOfferOrderRevisionStatus: string
{
    case Pending = 'PENDING';
    case Accepted = 'ACCEPTED';
    case Rejected = 'REJECTED';
    case Locked = 'LOCKED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
