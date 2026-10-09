<?php

namespace Modules\Services\Enums;


enum BoostCampaignStatus: string
{
    case Pending = 'PENDING';
    case Active = 'ACTIVE';
    case Paused = 'PAUSED';
    case Terminated = 'TERMINATED';
    case Expired = 'EXPIRED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}