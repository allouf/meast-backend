<?php

namespace Modules\Store\Http\Controllers\Seller\Statistics;

use App\Http\Controllers\Controller;
use Modules\Store\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewStatisticsController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    public function getReviewStatistics($storeId)
    {
        try {
            $userId = Auth::id();
            $statistics = $this->reviewService->getReviewStatisticsByStore($storeId, $userId);

            return $this->successResponse(
                'Review statistics retrieved successfully',
                $statistics
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
