<?php

namespace Modules\Store\Enums;

enum ViolationStatus: string
{
    case PENDING = 'pending';
    case REVIEWED = 'reviewed';
    case ACTION_TAKEN = 'action_taken';
    case IGNORED = 'ignored';
}
