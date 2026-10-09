<?php

namespace Modules\Community\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Community\Http\Resources\Admin\PostReportResource;
use Modules\Community\Services\PostReportService;

class PostReportController extends Controller
{
    protected $postReportservice;

    public function __construct(PostReportService $postReportservice)
    {
        $this->postReportservice = $postReportservice;
    }

    public function all(Request $request)
    {
        try {
            $data = $this->postReportservice->all();
            return $this->successResponse('Reports fetched successfully', [
                'reports'=> PostReportResource::collection($data['data']),
                'meta'=>$data['meta']
            ]);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function view($id)
    {
        try {
            $report = $this->postReportservice->show($id);
            return $this->successResponse('Report fetched successfully', new PostReportResource($report));
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function getByPost($postId)
    {
        try {
            $data = $this->postReportservice->getByPost($postId);
            return $this->successResponse('Reports for post fetched successfully', PostReportResource::collection($data));
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}