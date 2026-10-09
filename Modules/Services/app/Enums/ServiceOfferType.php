<?php

namespace Modules\Services\Enums;

enum ServiceOfferType: string
{
    case Hourly = 'hourly';
    case FixedPrice = 'fixed_price';
    case ProjectBased = 'project_based';
    case Retainer = 'retainer';
    case Package = 'package';
    case Subscription = 'subscription';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
