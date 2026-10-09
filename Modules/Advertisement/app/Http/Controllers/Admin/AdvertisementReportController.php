<?php

namespace Modules\Advertisement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Advertisement\Http\Requests\Admin\UpdateAdvertisementReportStatusRequest;
use Modules\Advertisement\Http\Resources\Admin\AdvertisementReportResource;
use Modules\Advertisement\Services\AdvertisementReportService;

class AdvertisementReportController extends Controller
{
    public function __construct(
        protected AdvertisementReportService $reportService
    ) {}

    public function all(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'status', 'advertisement_id', 'reason']);
            $reports = $this->reportService->getAllReports($filters);
            if (is_array($reports) && isset($reports['data'])) {
                return $this->successResponse(__('advertisement::messages.advertisement_reports.retrieved_successfully'), [
                    'data' => AdvertisementReportResource::collection($reports['data']),
                    'meta' => $reports['meta']
                ]);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_reports.retrieved_successfully'), AdvertisementReportResource::collection($reports));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show($id)
    {
        try {
            $report = $this->reportService->getReportById($id);
            if (!$report) {
                return $this->errorResponse(__('advertisement::messages.advertisement_reports.not_found'), 404);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_reports.retrieved_successfully'), new AdvertisementReportResource($report));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateStatus(UpdateAdvertisementReportStatusRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['reviewed_by'] = Auth::guard('admin')->id();
            $data['reviewed_at'] = now();
            $report = $this->reportService->updateReport($id, $data);
            if (!$report) {
                return $this->errorResponse(__('advertisement::messages.advertisement_reports.not_found'), 404);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_reports.status_updated_successfully'), new AdvertisementReportResource($report->load(['advertisement', 'reporter', 'reviewer'])));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
