<?php

namespace Modules\Services\Enums;

enum QuoteStatus: string
{
    case Pending = 'Pending';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
    case Canceled = 'Canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
