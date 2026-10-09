<?php

namespace Modules\Services\Enums;

enum CustomRequestOrderPaymentStatus: string
{
    case PendingPayment = 'Pending Payment';
    case EscrowFunded = 'Escrow Funded';
    case Released = 'Released';
    case Cancelled = 'Cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
