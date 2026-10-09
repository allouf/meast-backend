<?php

namespace Modules\Job\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Job\Models\Job;
use Modules\Job\Observers\JobObserver;
use Modules\Job\Repositories\Eloquent\JobApplicationInternalAttachmentRepository;
use Modules\Job\Repositories\Eloquent\JobApplicationNoteRepository;
use Modules\Job\Repositories\Eloquent\JobApplicationRepository;
use Modules\Job\Repositories\Eloquent\JobApplicationTagRepository;
use Modules\Job\Repositories\Eloquent\JobCategoryRepository;
use Modules\Job\Repositories\Eloquent\JobRepository;
use Modules\Job\Repositories\Eloquent\SkillRepository;
use Modules\Job\Repositories\Eloquent\JobTemplateRepository;
use Modules\Job\Repositories\Interfaces\JobApplicationInternalAttachmentRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobApplicationNoteRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobApplicationRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobApplicationTagRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobCategoryRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobRepositoryInterface;
use Modules\Job\Repositories\Interfaces\SkillRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobTemplateRepositoryInterface;
use Modules\Job\Repositories\Interfaces\UserCertificationRepositoryInterface;
use Modules\Job\Repositories\Interfaces\UserEducationRepositoryInterface;
use Modules\Job\Repositories\Interfaces\UserLanguageRepositoryInterface;
use Modules\Job\Repositories\Interfaces\UserLinkRepositoryInterface;
use Modules\Job\Repositories\Interfaces\UserProjectRepositoryInterface;
use Modules\Job\Repositories\Interfaces\UserSkillRepositoryInterface;
use Modules\Job\Repositories\Interfaces\UserWorkExperienceRepositoryInterface;
use Modules\Job\Repositories\Eloquent\UserCertificationRepository;
use Modules\Job\Repositories\Eloquent\UserEducationRepository;
use Modules\Job\Repositories\Eloquent\UserLanguageRepository;
use Modules\Job\Repositories\Eloquent\UserLinkRepository;
use Modules\Job\Repositories\Eloquent\UserProjectRepository;
use Modules\Job\Repositories\Eloquent\UserSkillRepository;
use Modules\Job\Repositories\Eloquent\UserWorkExperienceRepository;
use Modules\Job\Models\UserCertification;
use Modules\Job\Models\UserEducation;
use Modules\Job\Models\UserLanguage;
use Modules\Job\Models\UserLink;
use Modules\Job\Models\UserProject;
use Modules\Job\Models\UserSkill;
use Modules\Job\Models\UserWorkExperience;
use Modules\Job\Observers\ProfileCompletionObserver;
use Nwidart\Modules\Traits\PathNamespace;

class JobServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Job';
    protected string $nameLower = 'job';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        Job::observe(JobObserver::class);

        UserWorkExperience::observe(ProfileCompletionObserver::class);
        UserEducation::observe(ProfileCompletionObserver::class);
        UserSkill::observe(ProfileCompletionObserver::class);
        UserLanguage::observe(ProfileCompletionObserver::class);
        UserCertification::observe(ProfileCompletionObserver::class);
        UserProject::observe(ProfileCompletionObserver::class);
        UserLink::observe(ProfileCompletionObserver::class);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    public function register(): void
    {
        $this->app->bind(JobCategoryRepositoryInterface::class, JobCategoryRepository::class);
        $this->app->bind(SkillRepositoryInterface::class, SkillRepository::class);
        $this->app->bind(JobTemplateRepositoryInterface::class, JobTemplateRepository::class);
        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
        $this->app->bind(JobApplicationRepositoryInterface::class, JobApplicationRepository::class);
        $this->app->bind(JobApplicationNoteRepositoryInterface::class, JobApplicationNoteRepository::class);
        $this->app->bind(JobApplicationTagRepositoryInterface::class, JobApplicationTagRepository::class);
        $this->app->bind(JobApplicationInternalAttachmentRepositoryInterface::class, JobApplicationInternalAttachmentRepository::class);
        $this->app->bind(UserWorkExperienceRepositoryInterface::class, UserWorkExperienceRepository::class);
        $this->app->bind(UserEducationRepositoryInterface::class, UserEducationRepository::class);
        $this->app->bind(UserLanguageRepositoryInterface::class, UserLanguageRepository::class);
        $this->app->bind(UserSkillRepositoryInterface::class, UserSkillRepository::class);
        $this->app->bind(UserCertificationRepositoryInterface::class, UserCertificationRepository::class);
        $this->app->bind(UserProjectRepositoryInterface::class, UserProjectRepository::class);
        $this->app->bind(UserLinkRepositoryInterface::class, UserLinkRepository::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
