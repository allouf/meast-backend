<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Services\Services\ServiceOfferOrderRejectionReasonService;
use Illuminate\Http\Request;
use Modules\Services\Http\Requests\Admin\StoreRejectionReasonRequest;
use Modules\Services\Http\Resources\Admin\ServiceOfferOrderRejectionReasonResource;

class ServiceOrderRejectionReasonController extends Controller
{
    public function __construct(
        protected ServiceOfferOrderRejectionReasonService $service
    ) {}

    public function index()
    {
        $reasons = $this->service->getAll();

        return $this->successResponse(
            'Rejection reasons fetched successfully',
            ServiceOfferOrderRejectionReasonResource::collection($reasons)
        );
    }

    public function store(StoreRejectionReasonRequest $request)
    {
        $reason = $this->service->create($request->validated());

        return $this->successResponse(
            'Rejection reason created successfully',
            new ServiceOfferOrderRejectionReasonResource($reason)
        );
    }

    public function show(int $id)
    {
        $reason = $this->service->getById($id);

        return $this->successResponse(
            'Rejection reason fetched successfully',
            new ServiceOfferOrderRejectionReasonResource($reason)
        );
    }



    public function destroy(int $id)
    {
        $this->service->delete($id);

        return $this->successResponse('Rejection reason deleted successfully');
    }

    public function listActive()
    {
        $reasons = $this->service->getActive();

        return $this->successResponse(
            'Active rejection reasons fetched successfully',
            $reasons
        );
    }
}
