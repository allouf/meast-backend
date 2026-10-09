<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Job\Http\Resources\User\Job\JobProfileResource;
use Modules\Job\Http\Resources\User\Job\UserCertificationResource;
use Modules\Job\Http\Resources\User\Job\UserEducationResource;
use Modules\Job\Http\Resources\User\Job\UserLanguageResource;
use Modules\Job\Http\Resources\User\Job\UserLinkResource;
use Modules\Job\Http\Resources\User\Job\UserProjectResource;
use Modules\Job\Http\Resources\User\Job\UserSkillResource;
use Modules\Job\Http\Resources\User\Job\UserWorkExperienceResource;
use Illuminate\Support\Facades\Auth;

class ProfileSectionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->errorResponse(__('job::messages.user_not_found'));
        }

        $tab = $request->query('tab') ?? $request->query('key') ?? 'all';
        $allowed = ['profile', 'experiences', 'educations', 'languages', 'skills', 'certifications', 'projects', 'links', 'all'];
        if (! in_array($tab, $allowed, true)) {
            return $this->errorResponse('Invalid tab. Allowed: ' . implode(', ', $allowed), 422);
        }

        $sections = $tab === 'all' ? $allowed : [$tab];
        $sections = array_diff($sections, ['all']);
        $loadRelations = array_map(fn ($s) => match ($s) {
            'profile' => 'jobProfile',
            'experiences' => 'workExperiences',
            'educations' => 'educations',
            'languages' => 'languages',
            'skills' => 'skills.skill',
            'certifications' => 'certifications',
            'projects' => 'projects',
            'links' => 'links',
            default => null,
        }, $sections);
        $loadRelations = array_filter($loadRelations);
        if (! in_array('jobProfile', $loadRelations, true)) {
            $loadRelations[] = 'jobProfile';
        }
        $user->load($loadRelations);

        $data = [];
        $data['job_profile'] = $user->jobProfile ? new JobProfileResource($user->jobProfile) : null;
        if ($user->relationLoaded('workExperiences')) {
            $data['experiences'] = UserWorkExperienceResource::collection($user->workExperiences);
        }
        if ($user->relationLoaded('educations')) {
            $data['educations'] = UserEducationResource::collection($user->educations);
        }
        if ($user->relationLoaded('languages')) {
            $data['languages'] = UserLanguageResource::collection($user->languages);
        }
        if ($user->relationLoaded('skills')) {
            $data['skills'] = UserSkillResource::collection($user->skills);
        }
        if ($user->relationLoaded('certifications')) {
            $data['certifications'] = UserCertificationResource::collection($user->certifications);
        }
        if ($user->relationLoaded('projects')) {
            $data['projects'] = UserProjectResource::collection($user->projects);
        }
        if ($user->relationLoaded('links')) {
            $data['links'] = UserLinkResource::collection($user->links);
        }

        return $this->successResponse(__('job::messages.success'), $data);
    }
}
