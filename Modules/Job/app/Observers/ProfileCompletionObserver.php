<?php

namespace Modules\Job\Observers;

use Modules\Job\Services\ProfileCompletionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates the user's profile completion cache when profile-related models change.
 * Attach to: UserWorkExperience, UserEducation, UserSkill, UserLanguage,
 * UserCertification, UserProject, UserLink.
 */
class ProfileCompletionObserver
{
    public function __construct(
        protected ProfileCompletionService $profileCompletionService
    ) {}

    public function saved(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidateForModel($model);
    }

    private function invalidateForModel(Model $model): void
    {
        $userId = $model->user_id ?? null;
        if ($userId !== null) {
            $this->profileCompletionService->invalidateForUserId((int) $userId);
        }
    }
}
