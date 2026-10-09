<?php

namespace Modules\Job\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\Admin\Job\JobRequest;
use Modules\Job\Http\Resources\Admin\Job\JobResource;
use Modules\Job\Services\JobService;
use Exception;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(
        protected JobService $jobService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'per_page', 'page', 'employer_id', 'category_id', 'status', 'country_code',
                'employment_type', 'work_mode', 'search', 'sort',
                'application_deadline_from', 'application_deadline_to',
                'min_salary', 'max_salary',
            ]);
            $result = $this->jobService->getAllForAdmin($filters);
            return $this->successResponse(
                __('job::messages.jobs.retrieved_successfully'),
                [
                    'jobs' => JobResource::collection($result['data']),
                    'meta' => $result['meta'],
                ]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $id)
    {
        try {
            $job = $this->jobService->getById($id);
            if (! $job) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.jobs.retrieved_successfully'),
                new JobResource($job)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function create(JobRequest $request)
    {
        try {
            $job = $this->jobService->createJob($request->input('employer_id'), $request->validated());
            return $this->successResponse(
                __('job::messages.jobs.created_successfully'),
                new JobResource($job),
                201
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(JobRequest $request, int $id)
    {
        try {
            $job = $this->jobService->updateJob($id, $request->validated(), null);
            if (! $job) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.jobs.updated_successfully'),
                new JobResource($job)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function delete(int $id)
    {
        try {
            $deleted = $this->jobService->deleteJob($id, null);
            if (! $deleted) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.jobs.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        try {
            $request->validate(['status' => 'required|string|in:DRAFT,ACTIVE,PAUSED,CLOSED,EXPIRED']);
            $job = $this->jobService->updateStatus($id, $request->input('status'), null);
            if (! $job) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.jobs.status_updated_successfully'),
                new JobResource($job)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
