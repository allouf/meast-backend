<?php

namespace Modules\Store\Enums;

enum MasterOrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case PARTIALLY_FULFILLED = 'partially_fulfilled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
