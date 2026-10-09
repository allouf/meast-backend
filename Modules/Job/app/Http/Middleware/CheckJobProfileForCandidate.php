<?php

namespace Modules\Job\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Job\Services\ProfileCompletionService;
use Symfony\Component\HttpFoundation\Response;

class CheckJobProfileForCandidate
{
    /** HTTP status when job profile is not started (distinct from standard codes). */
    public const PROFILE_NOT_STARTED_STATUS = 480;

    public function __construct(
        protected ProfileCompletionService $profileCompletionService
    ) {}

    /**
     * Handle an incoming request.
     * Uses cached profile completion. Blocks if below minimum; otherwise sets percentage on request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $next($request);
        }

        // Companies (employers) cannot apply to jobs; only candidates can.
        if ($user->hasRole('company')) {
            return response()->json([
                'status' => false,
                'message' => __('job::messages.job_profile.company_cannot_apply'),
                'data' => ['code' => 'company_cannot_apply'],
            ], 403);
        }

        $percentage = $this->profileCompletionService->getPercentage($user);
        $minimum = $this->profileCompletionService->minimumPercentageToApply();

        if ($percentage <= 0 || $percentage < $minimum) {
            return response()->json([
                'status' => false,
                'message' => __('job::messages.job_profile.incomplete'),
                'data' => [
                    'profile_completion_percentage' => $percentage,
                    'code' => 'job_profile_incomplete',
                ],
            ], self::PROFILE_NOT_STARTED_STATUS);
        }

        $request->attributes->set('job_profile_completion_percentage', $percentage);

        return $next($request);
    }
}
