<?php

namespace App\Http\Controllers\Admin\System;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\StatisticsService;
use App\Helpers\ResponseHelper;

class StatisticsController extends Controller
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    public function statistics(Request $request)
    {
        $validated = request()->only([
            'country_code', 'region', 'category_id', 'from_date',
            'to_date'
        ]);
        try{
        $data = $this->statisticsService->getStatistics($validated);
        return $this->successResponse(__('fetch_success'),$data);

    } catch (Exception $e){
        return $this->exceptionResponse($e);
    }
    }

    public function analyticsByCategory()
    {
        $data = $this->statisticsService->getAnalyticsByCategory();
        return $this->successResponse(__('fetch_success'),$data);
    }
}
