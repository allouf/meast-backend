<?php

namespace Modules\Services\Enums;

enum MilestoneServiceStatus: string
{
    case Pending = 'pending';
    case InEscrow = 'in_escrow';
    case Paid = 'paid';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
