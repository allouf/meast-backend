<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\Seller\GetStorePolicyRequest;
use Modules\Store\Http\Requests\Seller\StorePolicyRequest;
use Modules\Store\Http\Resources\User\StorePolicyResource;
use Modules\Store\Services\StoreService;
use Exception;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function __construct(protected StoreService $storeService) {}
    public function getPolicy(GetStorePolicyRequest $request)
    {
        try {
            $data = $request->validated();
            $storeId = $data['store_id'];
            $type = $data['type'];

            $policy = $this->storeService->getPolicy($storeId, $type);

            if (!$policy) {
                return $this->errorResponse('Policy not found', 404);
            }

            return $this->successResponse('Policy retrieved successfully', new StorePolicyResource($policy));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function updateStorePolicy(StorePolicyRequest $request)
    {
        $data = $request->validated();
        try {
            $this->storeService->updateStorePolicy($data);
            return $this->successResponse('success');
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

