<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Session;

class LocationContextService
{
    protected const SESSION_KEY = 'location_context';
    protected const ENABLED_KEY = 'location_filtering_enabled';

    public function enable(): void
    {
        Session::put(self::ENABLED_KEY, true);
    }

    public function disable(): void
    {
        Session::put(self::ENABLED_KEY, false);
    }

    public function isEnabled(): bool
    {
        return Session::get(self::ENABLED_KEY, false);
    }

    public function setLocation(?string $countryCode = null, ?string $region = null): void
    {
        Session::put(self::SESSION_KEY, [
            'country_code' => $countryCode,
            'region' => $region,
            'detected_at' => now()->toIso8601String(),
        ]);
    }

    public function getLocation(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    public function getCountryCode(): ?string
    {
        $location = $this->getLocation();
        return $location['country_code'] ?? null;
    }

    public function getRegion(): ?string
    {
        $location = $this->getLocation();
        return $location['region'] ?? null;
    }

    public function hasLocation(): bool
    {
        if (!$this->isEnabled()) return false;
        $location = $this->getLocation();
        return $location !== null && (!empty($location['country_code']) || !empty($location['region']));
    }

    public function clearLocation(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::ENABLED_KEY);
    }

    public function getFilters(): array
    {
        if (!$this->isEnabled()) return [];
        $location = $this->getLocation();
        if (!$location) return [];
        $filters = [];
        if (!empty($location['country_code'])) $filters['country_code'] = $location['country_code'];
        if (!empty($location['region'])) $filters['region'] = $location['region'];
        return $filters;
    }
}
