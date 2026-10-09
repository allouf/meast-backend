<?php

namespace Modules\Services\Enums;

enum ServiceOfferReportReason: string
{
    case Spam = 'spam';
    case InappropriateContent = 'inappropriate_content';
    case Misleading = 'misleading';
    case Scam = 'scam';
    case Other = 'other';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
