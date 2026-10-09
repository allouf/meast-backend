<?php

namespace Modules\Services\Enums;


enum DeliveryTimeUnit: string
{
    case Minutes = 'minutes';
    case Hours = 'hours';
    case Days = 'days';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }


    public function toSeconds(int $value): int
    {
        return match ($this) {
            self::Minutes => $value * 60,
            self::Hours   => $value * 60 * 60,
            self::Days    => $value * 60 * 60 * 24,
        };
    }
}