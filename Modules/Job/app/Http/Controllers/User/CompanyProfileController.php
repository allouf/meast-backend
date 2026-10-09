<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Job\Http\Requests\User\Company\StoreCompanyTeamMemberRequest;
use Modules\Job\Http\Requests\User\Company\UpdateCompanyProfileRequest;
use Modules\Job\Http\Requests\User\Company\UpdateCompanyTeamMemberRequest;
use Modules\Job\Http\Resources\User\Company\CompanyProfileResource;
use Modules\Job\Http\Resources\User\Company\CompanyTeamMemberResource;
use Modules\Job\Http\Resources\User\Job\JobResource;
use Modules\Job\Models\CompanyTeamMember;
use Modules\Job\Services\CompanyProfileService;
use Modules\Job\Services\JobService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanyProfileController extends Controller
{
    public function __construct(
        protected CompanyProfileService $companyProfileService,
        protected JobService $jobService
    ) {}

    /**
     * View a company's full profile (for consumer role). By company id. Returns all company info.
     */
    public function viewCompany(int $id)
    {
        try {
            $company = $this->companyProfileService->getCompanyProfileById($id);
            return $this->successResponse(
                __('job::messages.company_profile.retrieved_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * View a company's full profile by user id (employer). For job-postings/companies/show/{userId}.
     */
    public function viewCompanyByUserId(int $userId)
    {
        try {
            $company = $this->companyProfileService->getCompanyProfileByUserId($userId);
            return $this->successResponse(
                __('job::messages.company_profile.retrieved_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Return active job postings by company (employer user_id). Public.
     * Query: per_page, page.
     */
    public function jobsByCompany(Request $request, int $userId)
    {
        try {
            $filters = $request->only(['per_page', 'page']);
            $filters['employer_id'] = $userId;
            $result = $this->jobService->getActiveJobs($filters);
            return $this->successResponse(
                __('job::messages.jobs.retrieved_successfully'),
                [
                    'jobs' => JobResource::collection($result['data']),
                    'meta' => $result['meta'],
                ]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    protected function getCompany()
    {
        $user = Auth::guard('api')->user();
        if (! $user || ! $user->hasRole('company') || ! $user->company) {
            throw new HttpException(404, __('job::messages.company_profile.company_not_found'));
        }
        return $user->company;
    }

    /**
     * Get company profile (logo, cover, description, gallery, team).
     */
    public function show()
    {
        try {
            $company = $this->getCompany();
            $company = $this->companyProfileService->getProfile($company);
            return $this->successResponse(
                __('job::messages.company_profile.retrieved_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update profile (description, logo, cover). Send as multipart/form-data with optional logo/cover files.
     */
    public function update(UpdateCompanyProfileRequest $request)
    {
        try {
            $company = $this->getCompany();
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $company = $this->companyProfileService->uploadLogo($company, $request->file('logo'));
            }
            if ($request->hasFile('cover')) {
                $company = $this->companyProfileService->uploadCover($company, $request->file('cover'));
            }

            $company = $this->companyProfileService->updateProfile($company, $data);
            return $this->successResponse(
                __('job::messages.company_profile.updated_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deleteLogo()
    {
        try {
            $company = $this->getCompany();

            $company = $this->companyProfileService->removeLogo($company);

            return $this->successResponse(
                __('job::messages.company_profile.logo_deleted_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deleteCover()
    {
        try {
            $company = $this->getCompany();

            $company = $this->companyProfileService->removeCover($company);

            return $this->successResponse(
                __('job::messages.company_profile.cover_deleted_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    /**
     * Add image to gallery.
     */
    public function addGalleryImage(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        try {
            $company = $this->getCompany();
            $files = $request->file('images');
            $company = $this->companyProfileService->addGalleryImages($company, $files);
            return $this->successResponse(
                __('job::messages.company_profile.gallery_image_added_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Remove image from gallery by index.
     */
    public function removeGalleryImage(Request $request, int $index)
    {
        try {
            $company = $this->getCompany();
            $company = $this->companyProfileService->removeGalleryImage($company, $index);
            return $this->successResponse(
                __('job::messages.company_profile.gallery_image_removed_successfully'),
                new CompanyProfileResource($company)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * List team members.
     */
    public function teamIndex()
    {
        try {
            $company = $this->getCompany();
            $members = $this->companyProfileService->getTeamMembers($company);
            return $this->successResponse(
                __('job::messages.company_profile.retrieved_successfully'),
                ['team_members' => CompanyTeamMemberResource::collection($members)]
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Add team member.
     */
    public function teamStore(StoreCompanyTeamMemberRequest $request)
    {
        try {
            $company = $this->getCompany();
            $member = $this->companyProfileService->addTeamMember(
                $company,
                $request->validated(),
                $request->file('image')
            );
            $company = $company->fresh('teamMembers');
            return $this->successResponse(
                __('job::messages.company_profile.team_member_added_successfully'),
                [
                    'team_member' => new CompanyTeamMemberResource($member),
                    'company_profile' => new CompanyProfileResource($company),
                ],
                201
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update team member.
     */
    public function teamUpdate(UpdateCompanyTeamMemberRequest $request, int $id)
    {
        try {
            $company = $this->getCompany();
            $member = CompanyTeamMember::findOrFail($id);
            $member = $this->companyProfileService->updateTeamMember(
                $company,
                $member,
                $request->validated(),
                $request->file('image')
            );
            return $this->successResponse(
                __('job::messages.company_profile.team_member_updated_successfully'),
                ['team_member' => new CompanyTeamMemberResource($member)]
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Delete team member.
     */
    public function teamDestroy(int $id)
    {
        try {
            $company = $this->getCompany();
            $member = CompanyTeamMember::findOrFail($id);
            $this->companyProfileService->deleteTeamMember($company, $member);
            return $this->successResponse(__('job::messages.company_profile.team_member_deleted_successfully'), null);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
