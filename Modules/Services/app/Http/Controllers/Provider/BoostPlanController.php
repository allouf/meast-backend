<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Services\Http\Resources\Provider\BoostPlanResource;
use Modules\Services\Services\BoostPlanService;

class BoostPlanController extends Controller
{
    public function __construct(
        protected BoostPlanService $boostPlanService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only('per_page');
            $result = $this->boostPlanService->getPaginated($filters);

            return $this->successResponse(
                __('services::messages.boost_plans.retrieved_successfully'),
                [
                    'boost_plans' => BoostPlanResource::collection($result->items()),
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
}
