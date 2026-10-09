<?php

namespace Modules\Store\Enums;

enum StoreStatus: string
{
    case ACTIVE = 'active';
    case WARNED = 'warned';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';
}
