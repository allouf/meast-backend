<?php

namespace Modules\Services\Enums;

enum ServiceOfferOrderStatus: string
{
    case Negotiation = 'NEGOTIATION';
    case PendingPayment = 'PENDING_PAYMENT';
    case Pending = 'PENDING';
    case InProgress = 'IN_PROGRESS';
    case Delivered = 'DELIVERED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case Rejected = 'REJECTED';
    case RevisionRequested = 'REVISION_REQUESTED';
    case InRevision = 'IN_REVISION';
    case Disputed = 'DISPUTED';



    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
