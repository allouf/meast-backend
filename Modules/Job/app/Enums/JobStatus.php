<?php

namespace Modules\Job\Enums;

enum JobStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case PAUSED = 'PAUSED';
    case CLOSED = 'CLOSED';
    case EXPIRED = 'EXPIRED';
}
