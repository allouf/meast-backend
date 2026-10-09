<?php

namespace Modules\Services\Enums;

enum CustomRequestStatus: string
{
    case Open = 'Open';
    case Awarded = 'Awarded';
    case Canceled = 'Canceled';
    case Expired = 'Expired';
    case InProgress = 'In Progress';
    case Completed = 'Completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
