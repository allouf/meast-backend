<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\SlaRuleResource;
use Modules\Store\Services\SlaRuleService;
use Modules\Store\Services\StoreMemberPermissionService;
use Illuminate\Http\Request;
use Modules\Store\Http\Requests\Seller\SlaRuleRequest;

class SlaRuleController extends Controller
{
    public function __construct(
        protected SlaRuleService $slaService,
        protected StoreMemberPermissionService $permissionService

    ) {}

    public function index($storeId)
    {
        $rules = $this->slaService->getStoreSlaRules($storeId);

        return $this->successResponse(__('store::messages.success'), SlaRuleResource::collection($rules));
    }

    public function show($id)
    {
        $rule = $this->slaService->findById($id);

        return $this->successResponse(
            __('store::messages.SLA Rule details fetched successfully'),
            new SlaRuleResource($rule)
        );
    }

    public function store(SlaRuleRequest $request)
    {
        $data = $request->validated();
        $storeId = $data['store_id'];

        $rule = $this->slaService->create($storeId, $data);

        return $this->successResponse(
            __('store::messages.SLA Rule created successfully'),
            new SlaRuleResource($rule)
        );
    }

    public function destroy($id)
    {
        $this->slaService->delete($id);

        return $this->successResponse(__('store::messages.SLA Rule deleted successfully'));
    }
}
