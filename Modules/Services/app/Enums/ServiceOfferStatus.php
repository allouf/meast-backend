<?php

namespace Modules\Services\Enums;

enum ServiceOfferStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Paused = 'paused';
    case Rejected = 'rejected';
    case Draft = 'draft';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
