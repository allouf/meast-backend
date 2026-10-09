<?php

namespace Modules\Services\Utilities;

use DateTimeInterface;

/**
 * Shared formatting helpers for the Services module (API payloads, order numbers, durations).
 */
final class ServiceFormatHelper
{
    /**
     * ISO-like datetime string for JSON resources (matches Carbon::toDateTimeString()).
     */
    public static function apiDateTime(DateTimeInterface|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }

    /**
     * Human duration from minutes: "2 days 3 hours" / "45 min" (buyer-facing detail style).
     */
    public static function durationFromMinutesDetailed(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 min';
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days.' day'.($days > 1 ? 's' : '');
        }

        if ($hours > 0) {
            $parts[] = $hours.' hour'.($hours > 1 ? 's' : '');
        }

        if ($mins > 0 && $days === 0) {
            $parts[] = $mins.' min';
        }

        return $parts !== [] ? implode(' ', $parts) : '0 min';
    }

    /**
     * Compact duration: "45 min" / "2 hour(s)" / "1 day(s)" (list + provider summary style).
     */
    public static function durationFromMinutesCompact(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        if ($minutes < 1440) {
            $hours = (int) floor($minutes / 60);

            return "{$hours} hour(s)";
        }

        $days = (int) floor($minutes / 1440);

        return "{$days} day(s)";
    }

    /**
     * Short labels without plural nuance (provider order detail delivery_time).
     */
    public static function durationFromMinutesShort(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        if ($minutes < 1440) {
            return floor($minutes / 60).' hour(s)';
        }

        return floor($minutes / 1440).' day(s)';
    }

    /**
     * Remaining time display like "2d 4h 15m".
     */
    public static function countdownFromMinutes(int $totalMinutes): string
    {
        if ($totalMinutes <= 0) {
            return '0m';
        }

        $days = intdiv($totalMinutes, 1440);
        $hours = intdiv($totalMinutes % 1440, 60);
        $minutes = $totalMinutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days}d";
        }
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }

        return $parts !== [] ? implode(' ', $parts) : '0m';
    }

    public static function formatServiceOfferOrderNumber(int $year, int $sequence, int $padLength = 6): string
    {
        return sprintf(
            'ORD-%d-%s',
            $year,
            str_pad((string) $sequence, $padLength, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Next numeric suffix for ORD-{YEAR}-{NNNNNN} given the latest order number in that year.
     */
    public static function nextServiceOfferOrderSequence(?string $lastOrderNumber): int
    {
        if ($lastOrderNumber === null || $lastOrderNumber === '') {
            return 1;
        }

        if (preg_match('/-(\d+)\s*$/', $lastOrderNumber, $matches)) {
            return (int) $matches[1] + 1;
        }

        return 1;
    }
}
