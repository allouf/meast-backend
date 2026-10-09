<?php

namespace Modules\Job\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\Admin\Job\JobCategoryRequest;
use Modules\Job\Http\Resources\Admin\Job\JobCategoryResource;
use Modules\Job\Services\JobCategoryService;
use Exception;
use Illuminate\Http\Request;

class JobCategoryController extends Controller
{
    public function __construct(
        protected JobCategoryService $jobCategoryService
    ) {}

    /**
     * Get all job categories.
     */
    public function all(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'status', 'search','sort_by',]);
            $categories = $this->jobCategoryService->getAllCategories($filters);

            if (isset($categories['data'], $categories['meta'])) {
                return $this->successResponse(
                    __('job::messages.job_categories.retrieved_successfully'),
                    [
                        'categories' => JobCategoryResource::collection($categories['data']),
                        'meta' => $categories['meta'],
                    ]
                );
            }

            return $this->successResponse(
                __('job::messages.job_categories.retrieved_successfully'),
                JobCategoryResource::collection($categories)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get job category by ID.
     */
    public function show(int $id)
    {
        try {
            $category = $this->jobCategoryService->getCategoryById($id);
            if (! $category) {
                return $this->errorResponse(__('job::messages.job_categories.not_found'), 404);
            }
            $category->loadCount('jobs');
            return $this->successResponse(
                __('job::messages.job_categories.retrieved_successfully'),
                new JobCategoryResource($category)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Create job category.
     */
    public function create(JobCategoryRequest $request)
    {
        try {
            $category = $this->jobCategoryService->createCategory($request->validated());
            return $this->successResponse(
                __('job::messages.job_categories.created_successfully'),
                new JobCategoryResource($category),
                201
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update job category.
     */
    public function update(JobCategoryRequest $request, int $id)
    {
        try {
            $category = $this->jobCategoryService->updateCategory($id, $request->validated());
            if (! $category) {
                return $this->errorResponse(__('job::messages.job_categories.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.job_categories.updated_successfully'),
                new JobCategoryResource($category)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Delete job category.
     */
    public function delete(int $id)
    {
        try {
            $this->jobCategoryService->deleteCategory($id);
            return $this->successResponse(__('job::messages.job_categories.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Toggle job category active status.
     */
    public function toggleStatus(int $id)
    {
        try {
            $category = $this->jobCategoryService->toggleCategoryStatus($id);
            return $this->successResponse(
                __('job::messages.job_categories.status_updated_successfully'),
                new JobCategoryResource($category)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
