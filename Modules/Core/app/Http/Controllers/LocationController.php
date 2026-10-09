<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Store\Models\Store;
use Modules\Core\Services\LocationContextService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected LocationContextService $locationService;

    public function __construct(LocationContextService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function getRegions(Request $request)
    {
        $query = $request->input('q', '');
        $countryCode = $request->input('country_code');

        if (!$countryCode && $this->locationService->isEnabled() && $this->locationService->hasLocation()) {
            $location = $this->locationService->getLocation();
            $countryCode = $countryCode ?? $location['country_code'] ?? null;
        }

        $regionsQuery = Store::withoutLocation()
            ->select('region')
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->distinct();

        if ($countryCode) {
            $regionsQuery->where('country_code', $countryCode);
        }

        if ($query) {
            $regionsQuery->where(function ($q) use ($query) {
                $q->where('region', 'LIKE', "%{$query}%")
                    ->orWhereRaw('LOWER(region) LIKE ?', ['%' . strtolower($query) . '%']);
            });
        }

        $regions = $regionsQuery->orderBy('region')
            ->limit(50)
            ->pluck('region')
            ->unique()
            ->values()
            ->map(function ($region) {
                return [
                    'value' => $region,
                    'label' => $region,
                    'label_ar' => $region,
                    'label_en' => $region,
                ];
            });

        return $this->successResponse('Regions retrieved successfully', $regions);
    }

    public function getCurrentLocation()
    {
        if (!$this->locationService->isEnabled()) {
            return $this->successResponse('Location filtering not enabled', null);
        }
        $location = $this->locationService->getLocation();
        if (!$location) {
            return $this->successResponse('No location set', null);
        }
        return $this->successResponse('Location retrieved successfully', $location);
    }

    public function setLocation(Request $request)
    {
        $request->validate([
            'country_code' => 'nullable|string|size:2',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
        ]);

        $this->locationService->enable();
        $this->locationService->setLocation(
            $request->input('country_code'),
            $request->input('region')
        );

        return $this->successResponse('Location set successfully', $this->locationService->getLocation());
    }

    public function getCities(Request $request)
    {
        $query = $request->input('q', '');
        $countryCode = $request->input('country_code');
        $region = $request->input('region');

        if (!$countryCode && $this->locationService->isEnabled() && $this->locationService->hasLocation()) {
            $location = $this->locationService->getLocation();
            $countryCode = $countryCode ?? $location['country_code'] ?? null;
            $region = $region ?? $location['region'] ?? null;
        }

        $citiesQuery = Store::withoutLocation()
            ->select('city')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct();

        if ($countryCode) {
            $citiesQuery->where('country_code', $countryCode);
        }
        if ($region) {
            $citiesQuery->where('region', $region);
        }
        if ($query) {
            $citiesQuery->where(function ($q) use ($query) {
                $q->where('city', 'LIKE', "%{$query}%")
                    ->orWhereRaw('LOWER(city) LIKE ?', ['%' . strtolower($query) . '%']);
            });
        }

        $cities = $citiesQuery->orderBy('city')
            ->limit(50)
            ->pluck('city')
            ->unique()
            ->values()
            ->map(fn($city) => ['value' => $city, 'label' => $city, 'label_ar' => $city, 'label_en' => $city]);

        return $this->successResponse('Cities retrieved successfully', $cities);
    }

    public function getCitiesByRegion(string $region, Request $request)
    {
        $query = $request->input('q', '');
        $countryCode = $request->input('country_code');

        if (!$countryCode && $this->locationService->isEnabled() && $this->locationService->hasLocation()) {
            $location = $this->locationService->getLocation();
            $countryCode = $location['country_code'] ?? null;
        }

        $citiesQuery = Store::withoutLocation()
            ->select('city')
            ->where('region', $region)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct();

        if ($countryCode) {
            $citiesQuery->where('country_code', $countryCode);
        }
        if ($query) {
            $citiesQuery->where(function ($q) use ($query) {
                $q->where('city', 'LIKE', "%{$query}%")
                    ->orWhereRaw('LOWER(city) LIKE ?', ['%' . strtolower($query) . '%']);
            });
        }

        $cities = $citiesQuery->orderBy('city')
            ->limit(50)
            ->pluck('city')
            ->unique()
            ->values()
            ->map(fn($city) => ['value' => $city, 'label' => $city, 'label_ar' => $city, 'label_en' => $city]);

        return $this->successResponse('Cities retrieved successfully', $cities);
    }
}
