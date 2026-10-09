<?php

namespace Modules\Services\Enums;

enum ServiceOfferOrderDisputeEvidenceType: string
{
    case Revision = 'REVISION';
    case Extension = 'EXTENSION';
    case Delivery = 'DELIVERY';
    case Message = 'MESSAGE';
    case System = 'SYSTEM'; // للأحداث التلقائية

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}