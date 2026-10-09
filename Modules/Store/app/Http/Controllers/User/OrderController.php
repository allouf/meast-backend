<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Models\MasterOrder;
use Modules\Store\Http\Requests\User\CreateOrderRequest;
use Modules\Store\Http\Requests\User\CheckCouponRequest;
use Modules\Store\Http\Requests\User\UpdateOrderStatusRequest;
use Modules\Store\Http\Resources\User\OrderResource;
use Modules\Store\Http\Resources\User\MasterOrderResource;
use Modules\Store\Services\OrderService;
use Modules\Store\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderService;
    protected $couponService;

    public function __construct(OrderService $orderService, CouponService $couponService)
    {
        $this->orderService = $orderService;
        $this->couponService = $couponService;
    }

    /**
     * Check/Validate coupon code
     */
    public function checkCoupon(CheckCouponRequest $request)
    {
        try {
            $userId = Auth::id();
            $data = $request->validated();

            $result = $this->couponService->validateCoupon(
                $data['coupon_code'],
                $data['items'],
                $userId
            );

            return $this->successResponse('Coupon is valid', $result);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Create order from cart
     */
    public function createOrder(CreateOrderRequest $request)
    {
        try {
            $userId = Auth::id();
            $data = $request->validated();

            $order = $this->orderService->createOrder($userId, $data);

            return $this->successResponse('Order created successfully', new MasterOrderResource($order));
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get my orders
     */
    public function getMyOrders()
    {
        try {
            $userId = Auth::id();
            $orders = $this->orderService->getMyOrders($userId);

            return $this->successResponse('Orders retrieved successfully', MasterOrderResource::collection($orders));
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getOrdersByStore(Request $request, $storeId)
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

    public function getOrder($orderId)
    {
        try {
            $userId = Auth::id();
            $order = $this->orderService->getOrderById($orderId, $userId);

            if ($order instanceof MasterOrder) {
                return $this->successResponse('Order retrieved successfully', new MasterOrderResource($order));
            } else {
                return $this->successResponse('Order retrieved successfully', new OrderResource($order));
            }
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update order status by buyer
     */
    public function updateStatusByBuyer(UpdateOrderStatusRequest $request, $orderId)
    {
        try {
            $userId = Auth::id();
            $status = $request->validated()['status'];
            $order = $this->orderService->updateOrderStatusByBuyer($orderId, $userId, $status);

            return $this->successResponse('Order status updated successfully', new OrderResource($order));
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateStatusBySeller(UpdateOrderStatusRequest $request, $orderId)
    {
        try {
            $userId = Auth::id();
            $status = $request->validated()['status'];
            $order = $this->orderService->updateOrderStatusBySeller($orderId, $userId, $status);

            return $this->successResponse('Order status updated successfully', new OrderResource($order));
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
   
}
