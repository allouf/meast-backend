<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Services\Services\ServiceOfferOrderExtensionReasonService;
use Illuminate\Http\Request;
use Modules\Services\Http\Resources\Admin\ServiceOfferOrderExtensionReasonResource;

class ServiceOfferOrderExtensionReasonController extends Controller
{
    public function __construct(
        protected ServiceOfferOrderExtensionReasonService $service
    ) {}

    public function index(Request $request)
    {
        $isActive = $request->has('is_active')
            ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;


        $reasons = $this->service->list(
            isset($isActive) ? (bool) $isActive : null
        );

        return $this->successResponse(
            'Extension reasons fetched successfully',
            ServiceOfferOrderExtensionReasonResource::collection($reasons)
        );
    }

    public function show(int $id)
    {
        $reason = $this->service->findById($id);

        return $this->successResponse(
            'Extension reason fetched successfully',
            new ServiceOfferOrderExtensionReasonResource($reason)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $reason = $this->service->create($request->all());

        return $this->successResponse(
            'Extension reason created successfully',
            new ServiceOfferOrderExtensionReasonResource($reason)
        );
    }

    public function toggle(int $id)
    {
        $reason = $this->service->toggle($id);

        return $this->successResponse(
            'Extension reason status toggled',
            new ServiceOfferOrderExtensionReasonResource($reason)
        );
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return $this->successResponse(
            'Extension reason deleted successfully'
        );
    }
}
