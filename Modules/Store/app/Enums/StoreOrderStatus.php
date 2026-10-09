<?php

namespace Modules\Store\Enums;

enum StoreOrderStatus: string
{
    case PLACED = 'placed';
    case SELLER_CONFIRMED = 'seller_confirmed';
    case IN_PROGRESS = 'in_progress';
    case FULFILLED = 'fulfilled';
    case COMPLETED = 'completed';
    case CANCELLED_BY_SELLER = 'cancelled_by_seller';
    case CANCELLED_TIMEOUT = 'cancelled_timeout';
    case RETURN_OPEN = 'return_open';
    case RETURN_APPROVED = 'return_approved';
    case RETURN_DECLINED = 'return_declined';
    case RETURN_RESOLVED = 'return_resolved';
}
