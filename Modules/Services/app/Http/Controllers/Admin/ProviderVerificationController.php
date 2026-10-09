<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Modules\Services\Http\Requests\Admin\ListProviderVerificationRequestsRequest;
use Modules\Services\Http\Requests\Admin\RejectProviderVerificationRequest;
use Modules\Services\Http\Requests\Admin\ReviewProviderVerificationRequest;
use Modules\Services\Http\Resources\Admin\ProviderVerificationRequestResource;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProviderVerificationController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService
    ) {}

    /**
     * List provider verification requests (default: pending).
     */
    public function index(ListProviderVerificationRequestsRequest $request)
    {
        try {
            $result = $this->serviceProviderService->getVerificationRequestsForAdmin(
                $request->validated()
            );

            return $this->successResponse(
                __('services::messages.service_provider.verification_requests_retrieved'),
                [
                    'requests' => ProviderVerificationRequestResource::collection($result->items()),
                    'meta' => [
                        'current_page' => $result->currentPage(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'total' => $result->total(),
                    ],
                ]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Show a single verification request.
     */
    public function show(int $id)
    {
        try {
            $profile = $this->serviceProviderService->getVerificationRequestByIdForAdmin($id);

            if (! $profile) {
                return $this->errorResponse(__('services::messages.service_provider.not_found'), 404);
            }

            return $this->successResponse(
                __('services::messages.service_provider.verification_request_retrieved'),
                new ProviderVerificationRequestResource($profile)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Approve a pending verification request.
     */
    public function approve(ReviewProviderVerificationRequest $request, int $id)
    {
        try {
            $profile = $this->serviceProviderService->approveVerification(
                $id,
                $request->validated()['verification_notes'] ?? null
            );

            return $this->successResponse(
                __('services::messages.service_provider.verification_approved'),
                new ProviderVerificationRequestResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Reject a pending verification request.
     */
    public function reject(RejectProviderVerificationRequest $request, int $id)
    {
        try {
            $profile = $this->serviceProviderService->rejectVerification(
                $id,
                $request->validated()['verification_notes']
            );

            return $this->successResponse(
                __('services::messages.service_provider.verification_rejected'),
                new ProviderVerificationRequestResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
