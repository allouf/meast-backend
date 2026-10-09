<?php

namespace Modules\Services\Utilities;

use Carbon\Carbon;

final class ServiceTimeHelper
{
    public static function countdown(
        ?Carbon $deadline
    ): array {

        if (! $deadline) {
            return self::expired(null);
        }

        $remainingMinutes = now()->diffInMinutes($deadline, false);

        if ($remainingMinutes <= 0) {
            return self::expired($deadline);
        }

        $days = intdiv($remainingMinutes, 1440);
        $hours = intdiv($remainingMinutes % 1440, 60);
        $minutes = $remainingMinutes % 60;

        return [
            'is_expired' => false,
            'total_minutes' => $remainingMinutes,

            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,

            'formatted' => "{$days}d {$hours}h {$minutes}m",

            'deadline' => $deadline->toDateTimeString(),
        ];
    }

    public static function fromCreatedAt(
        ?Carbon $createdAt,
        int $durationDays
    ): array {

        if (! $createdAt) {
            return self::expired(null);
        }

        return self::countdown(
            $createdAt->copy()->addDays($durationDays)
        );
    }

    private static function expired(?Carbon $deadline): array
    {
        return [
            'is_expired' => true,
            'total_minutes' => 0,
            'formatted' => 'Expired',

            'days' => 0,
            'hours' => 0,
            'minutes' => 0,

            'deadline' => $deadline?->toDateTimeString(),
        ];
    }
}
