<?php

namespace Modules\Job\Enums;

enum WorkMode: string
{
    case REMOTE = 'REMOTE';
    case ONSITE = 'ONSITE';
    case HYBRID = 'HYBRID';
}
