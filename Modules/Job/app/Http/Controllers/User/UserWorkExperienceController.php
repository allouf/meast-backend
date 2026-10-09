<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\User\Job\AddUserWorkExperienceRequest;
use Modules\Job\Http\Requests\User\Job\UpdateUserWorkExperienceRequest;
use Modules\Job\Http\Resources\User\Job\UserWorkExperienceResource;
use Modules\Job\Services\UserWorkExperienceService;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserWorkExperienceController extends Controller
{
    public function __construct(
        protected UserWorkExperienceService $service
    ) {}

    public function getMyExperiences()
    {
        try {
            $userId = Auth::id();
            $experiences = $this->service->getMyExperiences($userId);

            return $this->successResponse(
                'Work experiences retrieved successfully',
                UserWorkExperienceResource::collection($experiences)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function addExperience(AddUserWorkExperienceRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $created = collect($items)->map(fn (array $data) => $this->service->addExperience($userId, $data));

            return $this->successResponse(
                'Work experience(s) added successfully',
                UserWorkExperienceResource::collection($created)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateExperience(UpdateUserWorkExperienceRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $experience = $this->service->updateExperience($userId, $id, $request->validated());

            return $this->successResponse(
                'Work experience updated successfully',
                new UserWorkExperienceResource($experience)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeExperience(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->service->removeExperience($userId, $id);

            if ($deleted) {
                return $this->successResponse('Work experience removed successfully');
            }

            return $this->errorResponse('Failed to remove work experience', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

