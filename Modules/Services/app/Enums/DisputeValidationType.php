<?php

namespace Modules\Services\Enums;

enum DisputeValidationType: string
{
    case LateDelivery = 'late_delivery';
    case BadDelivery = 'bad_delivery';
    case ScopeCreep = 'scope_creep';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /*
    |----------------------------------------------------------
    | Helpers
    |----------------------------------------------------------
    */

    public function isLateDelivery(): bool
    {
        return $this === self::LateDelivery;
    }

    public function isBadDelivery(): bool
    {
        return $this === self::BadDelivery;
    }

    public function isScopeCreep(): bool
    {
        return $this === self::ScopeCreep;
    }
}