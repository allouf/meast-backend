<?php

namespace Modules\Services\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpFoundation\Response;

class CheckProviderProfileForService
{
    /** HTTP status when provider profile is missing bio, cover, and projects. */
    public const PROVIDER_PROFILE_INCOMPLETE_STATUS = 482;

    public function __construct(
        protected ServiceProviderService $serviceProviderService
    ) {
    }

    /**
     * Handle an incoming request.
     * Blocks actions if provider profile has no bio, cover, or projects.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Modules\Core\Models\User|null $user */
        $user = Auth::guard('api')->user();
        if (!$user) {
            return $next($request);
        }

        $profile = $this->serviceProviderService->getByUser($user);
        if (!$profile) {
            return response()->json([
                'status' => false,
                'message' => __('services::messages.service_provider.not_found'),
                'data' => ['code' => 'provider_profile_not_found'],
            ], self::PROVIDER_PROFILE_INCOMPLETE_STATUS);
        }

        $profile->loadCount('projects');

        $hasDescription = !empty(trim((string) $profile->bio));
        $hasCover = !empty($profile->cover);
        $hasProjects = $profile->projects_count > 0;

        if (!$hasDescription && !$hasCover && !$hasProjects) {
            return response()->json([
                'status' => false,
                'message' => __('services::messages.service_provider.provider_profile_incomplete'),
                'data' => [
                    'code' => 'provider_profile_incomplete',
                ],
            ], self::PROVIDER_PROFILE_INCOMPLETE_STATUS);
        }

        return $next($request);
    }
}
