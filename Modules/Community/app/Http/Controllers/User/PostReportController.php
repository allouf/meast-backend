<?php

namespace Modules\Community\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Community\Http\Requests\User\PostReportRequest;
use Modules\Community\Services\PostReportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostReportController extends Controller
{
    public function __construct(protected PostReportService $postReportService) {}

    public function getByUser(){
        
    }
    public function create(PostReportRequest $request)
    {
        $data = $request->validated();
        $data['reported_by'] = Auth::id();
        try {
            $this->postReportService->create($data);
            return $this->successResponse(__('create_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
