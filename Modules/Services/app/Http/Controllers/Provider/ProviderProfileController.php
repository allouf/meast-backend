<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\StoreProviderProfileRequest;
use Modules\Services\Http\Requests\Provider\SubmitProviderVerificationRequest;
use Modules\Services\Http\Requests\Provider\UpdateProviderProfileRequest;
use Modules\Services\Http\Resources\Provider\ProviderProfileResource;
use Modules\Services\Http\Resources\Provider\ProviderVerificationStatusResource;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProviderProfileController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService
    ) {}

    /**
     * Create provider profile (type: individual|company, display_name, languages, country_code, region, bio, identity_document, verification_notes).
     */
    public function store(StoreProviderProfileRequest $request)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 401);
        }

        try {
            $profile = $this->serviceProviderService->create(
                $user,
                $request->validated(),
                $request->file('identity_document')
            );
            return $this->successResponse(
                __('services::messages.service_provider.created_successfully'),
                new ProviderProfileResource($profile),
                201
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get current user's provider profile.
     */
    public function show()
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 401);
        }

        $profile = $this->serviceProviderService->getByUser($user);
        if (! $profile) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 404);
        }

        return $this->successResponse(
            __('services::messages.service_provider.retrieved_successfully'),
            new ProviderProfileResource($profile)
        );
    }

    /**
     * Update provider profile (partial: type, display_name, languages, country_code, region, bio, logo, cover, identity_document, verification_notes).
     */
    public function update(UpdateProviderProfileRequest $request)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 401);
        }

        $profile = $this->serviceProviderService->getByUser($user);
        if (! $profile) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 404);
        }

        try {
            $profile = $this->serviceProviderService->update(
                $profile,
                $request->validated(),
                $request->file('identity_document')
            );
            return $this->successResponse(
                __('services::messages.service_provider.updated_successfully'),
                new ProviderProfileResource($profile)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }


    /**
     * Verification status for a provider profile (own profile only).
     * GET /api/v1/service-provider/verification/{id}
     */
    public function verificationStatus(int $id)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 401);
        }

        try {
            $profile = $this->serviceProviderService->getVerificationStatusForProvider($id, $user);

            return $this->successResponse(
                __('services::messages.service_provider.verification_status_retrieved'),
                new ProviderVerificationStatusResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Submit provider profile for team verification review.
     */
    public function submitVerification(SubmitProviderVerificationRequest $request)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 401);
        }

        try {
            $profile = $this->serviceProviderService->submitVerification(
                $user,
                $request->validated(),
                $request->file('identity_document')
            );

            return $this->successResponse(
                __('services::messages.service_provider.verification_submitted'),
                new ProviderProfileResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeProfileField(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->errorResponse(__('services::messages.service_provider.not_found'), 401);
        }

        $request->validate([
            'field' => 'required|in:bio,cover,projects',
        ]);

        try {
            $profile = $this->serviceProviderService->removeProfileField($user, $request->field);

            return $this->successResponse(
                __('services::messages.service_provider.updated_successfully'),
                new ProviderProfileResource($profile)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
