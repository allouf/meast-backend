<?php

namespace Modules\Services\Enums;

use Illuminate\Database\Eloquent\Model;
use Modules\Services\Models\Milestone;
use Modules\Services\Models\ServiceOffer;
use ValueError;

enum OrderableType: string
{
    case ServiceOffer = 'service_offer';
    case MilestoneTemplate = 'milestone_template';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function modelClass(): string
    {
        return match ($this) {
            self::ServiceOffer => ServiceOffer::class,
            self::MilestoneTemplate => Milestone::class,
        };
    }

    public static function fromModel(Model $model): self
    {
        return match ($model::class) {
            ServiceOffer::class => self::ServiceOffer,
            Milestone::class => self::MilestoneTemplate,
            default => throw new ValueError('Unsupported orderable model: ' . $model::class),
        };
    }

    public static function tryFromPayload(string $type): ?self
    {
        return self::tryFrom($type);
    }
}
