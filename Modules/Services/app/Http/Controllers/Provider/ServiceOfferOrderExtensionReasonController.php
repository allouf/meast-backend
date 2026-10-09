<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Modules\Services\Services\ServiceOfferOrderExtensionReasonService;
use Illuminate\Http\Request;
use Modules\Services\Http\Resources\Provider\ServiceOfferOrderExtensionReasonResource;

class ServiceOfferOrderExtensionReasonController extends Controller
{
    public function __construct(
        protected ServiceOfferOrderExtensionReasonService $service
    ) {}

    public function index()
    {
        $reasons = $this->service->getActiveReasons();

        return $this->successResponse(
            'Active extension reasons fetched successfully',
            ServiceOfferOrderExtensionReasonResource::collection($reasons),
        );
    }
}
