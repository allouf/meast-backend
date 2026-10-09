<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\User\Job\AddUserCertificationRequest;
use Modules\Job\Http\Requests\User\Job\UpdateUserCertificationRequest;
use Modules\Job\Http\Resources\User\Job\UserCertificationResource;
use Modules\Job\Services\UserCertificationService;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserCertificationController extends Controller
{
    public function __construct(
        protected UserCertificationService $service
    ) {}

    public function getMyCertifications()
    {
        try {
            $userId = Auth::id();
            $certifications = $this->service->getMyCertifications($userId);

            return $this->successResponse(
                'Certifications retrieved successfully',
                UserCertificationResource::collection($certifications)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function addCertification(AddUserCertificationRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $created = collect($items)->map(fn (array $data) => $this->service->addCertification($userId, $data));

            return $this->successResponse(
                'Certification(s) added successfully',
                UserCertificationResource::collection($created)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateCertification(UpdateUserCertificationRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $certification = $this->service->updateCertification($userId, $id, $request->validated());

            return $this->successResponse(
                'Certification updated successfully',
                new UserCertificationResource($certification)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeCertification(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->service->removeCertification($userId, $id);

            if ($deleted) {
                return $this->successResponse('Certification removed successfully');
            }

            return $this->errorResponse('Failed to remove certification', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

