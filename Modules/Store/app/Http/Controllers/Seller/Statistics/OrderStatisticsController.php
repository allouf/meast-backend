<?php

namespace Modules\Store\Http\Controllers\Seller\Statistics;

use App\Http\Controllers\Controller;
use Modules\Store\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderStatisticsController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function getOrderStatistics($storeId)
    {
        try {
            $userId = Auth::id();
            $statistics = $this->orderService->getOrderStatisticsByStoreId($storeId, $userId);

            return $this->successResponse(
                'Order statistics retrieved successfully',
                $statistics
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
