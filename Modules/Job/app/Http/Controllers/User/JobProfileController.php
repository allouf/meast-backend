<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Job\Http\Requests\User\Job\UpdateJobProfileRequest;
use Modules\Job\Http\Resources\User\Job\JobProfileResource;
use Modules\Job\Services\JobProfileService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JobProfileController extends Controller
{
    public function __construct(
        protected JobProfileService $jobProfileService
    ) {}

    /**
     * Get current user's job profile (JobProfile model). For consumer role.
     */
    public function show()
    {
        try {
            $user = Auth::guard('api')->user();
            if (! $user) {
                return $this->errorResponse(__('job::messages.user_not_found'), 401);
            }
            $profile = $this->jobProfileService->getOrCreateForUser($user);
            return $this->successResponse(
                __('job::messages.job_profile.retrieved_successfully'),
                new JobProfileResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update current user's job profile (name, bio, avatar, cover). Multipart/form-data.
     */
    public function update(UpdateJobProfileRequest $request)
    {
        try {
            $user = Auth::guard('api')->user();
            if (! $user) {
                return $this->errorResponse(__('job::messages.user_not_found'), 401);
            }
            $profile = $this->jobProfileService->getOrCreateForUser($user);
            $data = $request->validated();

            if ($request->hasFile('avatar')) {
                $profile = $this->jobProfileService->uploadAvatar($profile, $request->file('avatar'));
            }
            if ($request->hasFile('cover')) {
                $profile = $this->jobProfileService->uploadCover($profile, $request->file('cover'));
            }

            $profile = $this->jobProfileService->updateProfile($profile, $data);
            return $this->successResponse(
                __('job::messages.job_profile.updated_successfully'),
                new JobProfileResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Add image(s) to job profile gallery.
     */
    public function addGallery(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        try {
            $user = Auth::guard('api')->user();
            if (! $user) {
                return $this->errorResponse(__('job::messages.user_not_found'), 401);
            }
            $profile = $this->jobProfileService->getOrCreateForUser($user);
            $profile = $this->jobProfileService->addGalleryImages($profile, $request->file('images'));
            return $this->successResponse(
                __('job::messages.company_profile.gallery_image_added_successfully'),
                new JobProfileResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Remove image from job profile gallery by index.
     */
    public function removeGallery(int $index)
    {
        try {
            $user = Auth::guard('api')->user();
            if (! $user) {
                return $this->errorResponse(__('job::messages.user_not_found'), 401);
            }
            $profile = $this->jobProfileService->getOrCreateForUser($user);
            $profile = $this->jobProfileService->removeGalleryImage($profile, $index);
            return $this->successResponse(
                __('job::messages.company_profile.gallery_image_removed_successfully'),
                new JobProfileResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
