<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Services\Http\Resources\Admin\ProviderProfileResource;
use Modules\Services\Services\ServiceProviderService;

class ProviderProfileController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService
    ) {}

    /**
     * Get all provider profiles (paginated).
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'search', 'type']);
            $result = $this->serviceProviderService->getAllForAdmin($filters);
            return $this->successResponse(
                __('services::messages.service_provider.retrieved_successfully'),
                [
                    'providers' => ProviderProfileResource::collection($result->items()),
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
     * Get provider profile by ID (with user, languages, service offers).
     */
    public function show(int $id)
    {
        try {
            $profile = $this->serviceProviderService->getByIdForAdmin($id);
            if (! $profile) {
                return $this->errorResponse(__('services::messages.service_provider.not_found'), 404);
            }
            return $this->successResponse(
                __('services::messages.service_provider.retrieved_successfully'),
                new ProviderProfileResource($profile)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
