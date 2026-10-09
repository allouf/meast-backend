<?php

namespace Modules\Services\Enums;

enum DeliveryModel: string
{
    case Remote = 'remote';
    case OnSite = 'on_site';
    case Hybrid = 'hybrid';
    case InPerson = 'in_person';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
