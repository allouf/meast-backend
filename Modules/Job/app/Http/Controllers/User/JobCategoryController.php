<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Job\Http\Resources\User\Job\JobCategoryListResource;
use Modules\Job\Services\JobCategoryService;

class JobCategoryController extends Controller
{
    public function __construct(
        protected JobCategoryService $jobCategoryService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'paginate', 'search']);
        $filters['active_only'] = true;
        $filters['paginate'] = filter_var($filters['paginate'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $result = $this->jobCategoryService->getAllCategories($filters);
        if (isset($result['data'], $result['meta'])) {
            return $this->successResponse(
                __('job::messages.job_categories.retrieved_successfully'),
                ['categories' => JobCategoryListResource::collection($result['data']), 'meta' => $result['meta']]
            );
        }
        return $this->successResponse(__('job::messages.job_categories.retrieved_successfully'), JobCategoryListResource::collection($result));
    }
}
