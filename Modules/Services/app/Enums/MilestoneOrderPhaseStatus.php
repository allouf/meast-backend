<?php

namespace Modules\Services\Enums;

enum MilestoneOrderPhaseStatus: string
{
    case PendingPayment = 'PENDING_PAYMENT';
    case InProgress = 'IN_PROGRESS';
    case Delivered = 'DELIVERED';
    case InRevision = 'IN_REVISION';
    case Completed = 'COMPLETED';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
