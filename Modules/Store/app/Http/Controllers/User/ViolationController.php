<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Store\Http\Requests\User\SubmitViolationRequest;
use Modules\Store\Http\Resources\User\ViolationReasonResource;
use Modules\Store\Services\ViolationReasonService;
use Modules\Store\Services\ViolationService;

class ViolationController extends Controller
{
    public function __construct(
        protected ViolationService $violationService,
        protected ViolationReasonService $violationReasonService
    ) {}

    public function submit(SubmitViolationRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        try {
            $this->violationService->create($data);
            return $this->successResponse('success');
        } catch (ValidationException $e) {
            return $this->exceptionResponse($e);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'An error occurred while submitting violation.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getReasons()
    {
        try {
            $reasons = $this->violationReasonService->getActiveReasons();
            return $this->successResponse(
                'Violation reasons retrieved successfully',
                ViolationReasonResource::collection($reasons)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'An error occurred while retrieving violation reasons.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
