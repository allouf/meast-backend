<?php

namespace Modules\Services\Enums;


enum ServiceOfferOrderExtensionStatus: string
{

    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
