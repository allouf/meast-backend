<?php

namespace Modules\Advertisement\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Advertisement\Http\Requests\User\AdvertisementReportRequest;
use Modules\Advertisement\Http\Resources\User\AdvertisementReportResource;
use Modules\Advertisement\Services\AdvertisementReportService;

class AdvertisementReportController extends Controller
{
    public function __construct(
        protected AdvertisementReportService $reportService
    ) {}

    public function store(AdvertisementReportRequest $request)
    {
        if ($this->reportService->hasUserReported($request->advertisement_id)) {
            return $this->errorResponse(__('advertisement::messages.advertisement_reports.already_reported'), 400);
        }
        try {
            $report = $this->reportService->createReport($request->validated());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_reports.created_successfully'), new AdvertisementReportResource($report), 201);
    }

    public function myReports(Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $reports = $this->reportService->getMyReports($filters);
        if (is_array($reports) && isset($reports['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisement_reports.my_reports_retrieved_successfully'), [
                'data' => AdvertisementReportResource::collection($reports['data']),
                'meta' => $reports['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_reports.my_reports_retrieved_successfully'), AdvertisementReportResource::collection($reports));
    }

    public function show($id)
    {
        $report = $this->reportService->getReportById($id);
        if (!$report) {
            return $this->errorResponse(__('advertisement::messages.advertisement_reports.not_found'), 404);
        }
        if ($report->reported_by !== Auth::id() && !Auth::guard('admin')->check()) {
            return $this->errorResponse(__('advertisement::messages.advertisement_reports.unauthorized'), 403);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_reports.retrieved_successfully'), new AdvertisementReportResource($report));
    }

    public function check($advertisementId)
    {
        $hasReported = $this->reportService->hasUserReported($advertisementId);
        return $this->successResponse(__('advertisement::messages.advertisement_reports.check_successfully'), ['has_reported' => $hasReported]);
    }
}
