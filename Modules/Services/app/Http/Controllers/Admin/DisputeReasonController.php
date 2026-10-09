<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Services\Enums\DisputeValidationType;
use Modules\Services\Http\Resources\Admin\DisputeReasonResource;
use Modules\Services\Services\DisputeReasonService;

class DisputeReasonController extends Controller
{

    public function __construct(
        protected DisputeReasonService $service
    ) {}

    public function index(Request $request)
    {
        $isActive = $request->has('is_active')
            ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $reasons = $this->service->list($isActive);

        return $this->successResponse(
            'Dispute reasons fetched successfully',
            DisputeReasonResource::collection($reasons)
        );
    }

    public function show(int $id)
    {
        $reason = $this->service->findById($id);

        return $this->successResponse(
            'Dispute reason fetched successfully',
            new DisputeReasonResource($reason)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'validation_type' => ['required', 'in:' . implode(',', DisputeValidationType::values())],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $reason = $this->service->create($request->all());

        return $this->successResponse(
            'Dispute reason created successfully',
            new DisputeReasonResource($reason)
        );
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'validation_type' => ['sometimes', 'in:' . implode(',', DisputeValidationType::values())],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $reason = $this->service->update($id, $request->all());

        return $this->successResponse(
            'Dispute reason updated successfully',
            new DisputeReasonResource($reason)
        );
    }

    public function toggle(int $id)
    {
        $reason = $this->service->toggle($id);

        return $this->successResponse(
            'Dispute reason status toggled',
            new DisputeReasonResource($reason)
        );
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return $this->successResponse(
            'Dispute reason deleted successfully'
        );
    }
}
