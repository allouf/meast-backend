<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Services\Http\Resources\Provider\DisputeReasonResource;
use Modules\Services\Services\DisputeReasonService;

class DisputeReasonController extends Controller
{
    public function __construct(
        protected DisputeReasonService $service
    ) {}


    public function index()
    {
        $reasons = $this->service->list(true);

        return $this->successResponse(
            'Active dispute reasons fetched successfully',
            DisputeReasonResource::collection($reasons)
        );
    }
}
