<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\User\Job\AddUserEducationRequest;
use Modules\Job\Http\Requests\User\Job\UpdateUserEducationRequest;
use Modules\Job\Http\Resources\User\Job\UserEducationResource;
use Modules\Job\Services\UserEducationService;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserEducationController extends Controller
{
    public function __construct(
        protected UserEducationService $service
    ) {}

    public function getMyEducations()
    {
        try {
            $userId = Auth::id();
            $educations = $this->service->getMyEducations($userId);

            return $this->successResponse(
                'Educations retrieved successfully',
                UserEducationResource::collection($educations)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function addEducation(AddUserEducationRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $created = collect($items)->map(fn (array $data) => $this->service->addEducation($userId, $data));

            return $this->successResponse(
                'Education(s) added successfully',
                UserEducationResource::collection($created)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateEducation(UpdateUserEducationRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $education = $this->service->updateEducation($userId, $id, $request->validated());

            return $this->successResponse(
                'Education updated successfully',
                new UserEducationResource($education)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeEducation(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->service->removeEducation($userId, $id);

            if ($deleted) {
                return $this->successResponse('Education removed successfully');
            }

            return $this->errorResponse('Failed to remove education', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

