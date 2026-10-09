<?php

namespace App\Utilities;


class GlobalHelper
{
    public static function generate(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    public static function formatDeviceName(?string $userAgent): string
    {
        $userAgent = $userAgent ?? '';
        // ---- OS detection ----
        $os = 'Unknown OS';

        if (preg_match('/Windows NT 10.0/i', $userAgent)) {
            $os = 'Windows 10';
        } elseif (preg_match('/Windows NT 11.0/i', $userAgent)) {
            $os = 'Windows 11';
        } elseif (preg_match('/Mac OS X ([\d_]+)/i', $userAgent, $m)) {
            $os = 'macOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Android ([\d\.]+)/i', $userAgent, $m)) {
            $os = 'Android ' . $m[1];
        } elseif (preg_match('/iPhone OS ([\d_]+)/i', $userAgent, $m)) {
            $os = 'iOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        }

        // ---- Device type ----
        $deviceType = 'Desktop';

        if (preg_match('/Mobile|iPhone|Android/i', $userAgent)) {
            $deviceType = 'Mobile';
        } elseif (preg_match('/Tablet|iPad/i', $userAgent)) {
            $deviceType = 'Tablet';
        }

        $browser = 'Unknown Browser';

        if (preg_match('/Edg\/([\d\.]+)/i', $userAgent, $m)) {
            $browser = 'Edge ' . intval($m[1]);
        } elseif (preg_match('/Chrome\/([\d\.]+)/i', $userAgent, $m)) {
            $browser = 'Chrome ' . intval($m[1]);
        } elseif (preg_match('/Firefox\/([\d\.]+)/i', $userAgent, $m)) {
            $browser = 'Firefox ' . intval($m[1]);
        } elseif (
            preg_match('/Safari\/([\d\.]+)/i', $userAgent)
            && preg_match('/Version\/([\d\.]+)/i', $userAgent, $m)
        ) {
            $browser = 'Safari ' . intval($m[1]);
        }

        return "{$os} • {$deviceType} • {$browser}";
    }
}
