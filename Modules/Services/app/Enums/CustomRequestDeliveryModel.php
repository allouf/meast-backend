<?php

namespace Modules\Services\Enums;

enum CustomRequestDeliveryModel: string
{
    case Remote = 'Remote';
    case Onsite = 'Onsite';
    case Hybrid = 'Hybrid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
