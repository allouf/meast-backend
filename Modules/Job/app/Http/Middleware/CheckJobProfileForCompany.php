<?php

namespace Modules\Job\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckJobProfileForCompany
{
    /** HTTP status when company profile has no description, gallery, or team. */
    public const COMPANY_PROFILE_INCOMPLETE_STATUS = 478;

    /**
     * Handle an incoming request.
     * Blocks submitting/updating job postings if company has no description, gallery, or team.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();
        if (! $user || ! $user->hasRole('company')) {
            return $next($request);
        }

        $company = $user->company;
        if (! $company) {
            return response()->json([
                'status' => false,
                'message' => __('job::messages.company_profile.company_not_found'),
                'data' => ['code' => 'company_profile_not_found'],
            ], self::COMPANY_PROFILE_INCOMPLETE_STATUS);
        }

        $company->loadCount('teamMembers');
        $company->load('jobProfile');
        $profile = $company->jobProfile;
        $hasDescription = $profile && ! empty(trim((string) $profile->bio));
        $gallery = $profile && is_array($profile->gallery) ? $profile->gallery : [];
        $hasGallery = count($gallery) > 0;
        $hasTeam = $company->team_members_count > 0;

        if (! $hasDescription && ! $hasGallery && ! $hasTeam) {
            return response()->json([
                'status' => false,
                'message' => __('job::messages.job_profile.company_profile_incomplete'),
                'data' => [
                    'code' => 'company_profile_incomplete',
                ],
            ], self::COMPANY_PROFILE_INCOMPLETE_STATUS);
        }

        return $next($request);
    }
}
