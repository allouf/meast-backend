<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Modules\Services\Http\Requests\Admin\BoostPlanRequest;
use Modules\Services\Http\Resources\Admin\BoostPlanResource;
use Modules\Services\Services\BoostPlanService;

class BoostPlanController extends Controller
{
    public function __construct(
        protected BoostPlanService $boostPlanService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'search', 'is_active']);
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

    public function show(int $id)
    {
        try {
            $plan = $this->boostPlanService->findById($id);
            if (! $plan) {
                return $this->errorResponse(__('services::messages.boost_plans.not_found'), 404);
            }

            return $this->successResponse(
                __('services::messages.boost_plans.retrieved_successfully'),
                new BoostPlanResource($plan)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function store(BoostPlanRequest $request)
    {
        try {
            $plan = $this->boostPlanService->createPlan($request->validated());

            return $this->successResponse(
                __('services::messages.boost_plans.created_successfully'),
                new BoostPlanResource($plan),
                201
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(BoostPlanRequest $request, int $id)
    {
        try {
            $plan = $this->boostPlanService->updatePlan($request->validated(), $id);

            return $this->successResponse(
                __('services::messages.boost_plans.updated_successfully'),
                new BoostPlanResource($plan)
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('services::messages.boost_plans.not_found'), 404);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->boostPlanService->delete($id);

            return $this->successResponse(__('services::messages.boost_plans.deleted_successfully'), null);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('services::messages.boost_plans.not_found'), 404);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function toggleStatus(int $id)
    {
        try {
            $plan = $this->boostPlanService->toggleActivation($id);

            return $this->successResponse(
                __('services::messages.boost_plans.status_updated_successfully'),
                new BoostPlanResource($plan)
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('services::messages.boost_plans.not_found'), 404);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
