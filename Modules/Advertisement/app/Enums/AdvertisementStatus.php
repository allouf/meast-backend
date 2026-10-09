<?php

namespace Modules\Advertisement\Enums;

enum AdvertisementStatus: string
{
    case DRAFT = 'draft';
    case UNDER_REVIEW = 'under_review';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case SOLD = 'sold';
    case EXPIRED = 'expired';
    case ARCHIVED = 'archived';
    case REJECTED = 'rejected';
}
