<?php

namespace Modules\Job\Services;

use Modules\Core\Models\Company;
use Modules\Job\Models\CompanyTeamMember;
use Modules\Job\Models\JobProfile;
use Modules\Core\Repositories\Interfaces\CompanyRepositoryInterface;
use App\Utilities\FileManager;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanyProfileService
{
    private const GALLERY_DIR = 'job_profiles/gallery';
    private const COVER_DIR = 'job_profiles/cover';
    private const LOGO_DIR = 'companies/logo';
    private const TEAM_IMAGE_DIR = 'companies/team';

    public function __construct(
        protected CompanyRepositoryInterface $companyRepository
    ) {}

    public function getProfile(Company $company): Company
    {
        return $company->load(['teamMembers', 'jobProfile']);
    }

    protected function getOrCreateJobProfileForCompany(Company $company): JobProfile
    {
        $profile = JobProfile::firstOrCreate(
            ['user_id' => $company->user_id],
            ['user_id' => $company->user_id]
        );
        return $profile;
    }

    /**
     * Get company profile by id (for consumer/public view). Returns full profile with team.
     */
    public function getCompanyProfileById(int $companyId): Company
    {
        $company = $this->companyRepository->findById($companyId);
        if (! $company) {
            throw new HttpException(404, __('job::messages.company_profile.company_not_found'));
        }
        return $this->getProfile($company);
    }

    /**
     * Get company profile by user id (employer user id). For job-postings/companies/show.
     */
    public function getCompanyProfileByUserId(int $userId): Company
    {
        $company = $this->companyRepository->findByUserId($userId);
        if (! $company) {
            throw new HttpException(404, __('job::messages.company_profile.company_not_found'));
        }
        return $this->getProfile($company);
    }

    /**
     * Paginated list of companies (for public job/company search). Same shape as JobService::getActiveJobs.
     *
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getPaginatedCompanies(array $filters = []): array
    {
        return $this->companyRepository->all($filters);
    }

    public function updateProfile(Company $company, array $data): Company
    {
        $profile = $this->getOrCreateJobProfileForCompany($company);
        if (array_key_exists('description', $data)) {
            $profile->update(['bio' => $data['description']]);
        }
        return $company->fresh(['teamMembers', 'jobProfile']);
    }

    public function uploadLogo(Company $company, UploadedFile $file): Company
    {
        if ($company->logo) {
            FileManager::delete($company->logo);
        }
        $path = FileManager::upload(self::LOGO_DIR, $file);
        $this->companyRepository->update($company, ['logo' => $path]);
        return $company->fresh(['teamMembers', 'jobProfile']);
    }

    public function uploadCover(Company $company, UploadedFile $file): Company
    {
        $profile = $this->getOrCreateJobProfileForCompany($company);
        if ($profile->cover) {
            FileManager::delete($profile->cover);
        }
        $path = FileManager::upload(self::COVER_DIR, $file);
        $profile->update(['cover' => $path]);
        return $company->fresh(['teamMembers', 'jobProfile']);
    }

    public function removeLogo(Company $company): Company
    {

        if (!$company->logo) {
            throw new HttpException(404, __('job::messages.company_profile.logo_not_found'));
        }

        FileManager::delete($company->logo);


        $this->companyRepository->update($company, [
            'logo' => null
        ]);

        return $company->fresh(['teamMembers', 'jobProfile']);
    }


    public function removeCover(Company $company): Company
    {
        $profile = $this->getOrCreateJobProfileForCompany($company);

        if (!$profile->cover) {
            throw new HttpException(404, __('job::messages.company_profile.cover_not_found'));
        }

        FileManager::delete($profile->cover);


        $profile->update([
            'cover' => null
        ]);

        return $company->fresh(['teamMembers', 'jobProfile']);
    }

    public function addGalleryImage(Company $company, UploadedFile $file): Company
    {
        return $this->addGalleryImages($company, [$file]);
    }

    /**
     * @param  array<UploadedFile>  $files
     */
    public function addGalleryImages(Company $company, array $files): Company
    {
        $profile = $this->getOrCreateJobProfileForCompany($company);
        $gallery = $profile->gallery ?? [];
        foreach ($files as $file) {
            $gallery[] = FileManager::upload(self::GALLERY_DIR, $file);
        }
        if (! empty($files)) {
            $profile->update(['gallery' => $gallery]);
        }
        return $company->fresh(['teamMembers', 'jobProfile']);
    }

    public function removeGalleryImage(Company $company, int $index): Company
    {
        $profile = $this->getOrCreateJobProfileForCompany($company);
        $gallery = $profile->gallery ?? [];
        if (! isset($gallery[$index])) {
            throw new HttpException(404, __('job::messages.company_profile.gallery_image_not_found'));
        }
        FileManager::delete($gallery[$index]);
        array_splice($gallery, $index, 1);
        $profile->update(['gallery' => $gallery]);
        return $company->fresh(['teamMembers', 'jobProfile']);
    }

    public function getTeamMembers(Company $company)
    {
        return $company->teamMembers()->orderBy('sort_order')->get();
    }

    public function addTeamMember(Company $company, array $data, ?UploadedFile $image = null): CompanyTeamMember
    {
        $imagePath = $image ? FileManager::upload(self::TEAM_IMAGE_DIR, $image) : null;
        $maxOrder = $company->teamMembers()->max('sort_order') ?? 0;
        return $company->teamMembers()->create([
            'name' => $data['name'],
            'position' => $data['position'] ?? null,
            'image' => $imagePath,
            'sort_order' => $maxOrder + 1,
        ]);
    }

    public function updateTeamMember(Company $company, CompanyTeamMember $member, array $data, ?UploadedFile $image = null): CompanyTeamMember
    {
        if ($member->company_id !== $company->id) {
            throw new HttpException(403, __('job::messages.company_profile.team_member_not_owned'));
        }
        $update = ['name' => $data['name'], 'position' => $data['position'] ?? null];
        if ($image) {
            if ($member->image) {
                FileManager::delete($member->image);
            }
            $update['image'] = FileManager::upload(self::TEAM_IMAGE_DIR, $image);
        }
        $member->update($update);
        return $member->fresh();
    }

    public function deleteTeamMember(Company $company, CompanyTeamMember $member): void
    {
        if ($member->company_id !== $company->id) {
            throw new HttpException(403, __('job::messages.company_profile.team_member_not_owned'));
        }
        if ($member->image) {
            FileManager::delete($member->image);
        }
        $member->delete();
    }
}
