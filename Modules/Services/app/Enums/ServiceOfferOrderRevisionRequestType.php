<?php

namespace Modules\Services\Enums;

enum ServiceOfferOrderRevisionRequestType: string
{
    case Standard = 'STANDARD';
    case Minor = 'MINOR';
    case Clarification = 'CLARIFICATION';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
