<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\Admin\MasterOrderListResource;
use Modules\Store\Http\Resources\Admin\MasterOrderResource;
use Modules\Store\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'status',
                'date_from',
                'date_to',
                'date',
                // 'store_id',
                // 'payment_method',
                // 'shipping_method',
                'search',
                'per_page'
            ]);

            $result = $this->orderService->getAllMasterOrders($filters);

            return $this->successResponse(
                'Master orders retrieved successfully',
                [
                    'orders' => MasterOrderListResource::collection($result['data']),
                    'meta' => $result['meta']
                ]
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $masterOrderId)
    {
        try {
            $order = $this->orderService->getMasterOrderDetails($masterOrderId);

            return $this->successResponse(
                'Master order retrieved successfully',
                new MasterOrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
