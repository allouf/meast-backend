<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Models\MasterOrder;
use Modules\Store\Http\Requests\User\UpdateOrderStatusRequest;
use Modules\Store\Http\Resources\User\OrderResource;
use Modules\Store\Http\Resources\User\MasterOrderResource;
use Modules\Store\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}
    public function index(Request $request, $storeId)
    {
        try {
            $userId = Auth::id();
            $filters = $request->only([
                'status',
                'date_from',
                'date_to',
                'date',
                'shipping_method',
                'payment_method',
                'search',
                'per_page'
            ]);
            $result = $this->orderService->getOrdersByStoreId($storeId, $userId, $filters);
            return $this->successResponse(
                'Orders retrieved successfully',
                [
                    'orders' => OrderResource::collection($result['data']),
                    'meta' => $result['meta']
                ]
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function show($orderId)
    {
        try {
            $userId = Auth::id();
            $order = $this->orderService->getOrderById($orderId, $userId);
            if ($order instanceof MasterOrder) {
                return $this->successResponse(
                    'Order details retrieved successfully',
                    new MasterOrderResource($order)
                );
            }
            return $this->successResponse(
                'Order details retrieved successfully',
                new OrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function updateStatus(UpdateOrderStatusRequest $request, $orderId)
    {
        try {
            $status = $request->validated()['status'];

            $this->orderService->updateOrderStatus(
                $orderId,
                $status
            );

            return $this->successResponse('Order status updated successfully');
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
