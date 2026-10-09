<?php

namespace App\Http\Middleware;

use Modules\Core\Services\LocationContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocationMiddleware
{
    protected LocationContextService $locationService;

    public function __construct(LocationContextService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Handle an incoming request.
     * Only processes headers - no GeoIP fallback
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enable location filtering for this request
        $this->locationService->enable();

        // Extract location from headers only
        $countryCode = $request->header('X-Country-Code');
        $region = $request->header('X-Region');

        // Set location if any header is provided
        if ($countryCode || $region) {
            $this->locationService->setLocation($countryCode, $region);
        }

        // Make location service available in request
        $request->attributes->set('location_service', $this->locationService);

        return $next($request);
    }
}
