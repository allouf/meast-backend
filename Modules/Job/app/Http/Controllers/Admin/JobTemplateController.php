<?php

namespace Modules\Job\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\Admin\Job\JobTemplateRequest;
use Modules\Job\Http\Resources\Admin\Job\JobTemplateResource;
use Modules\Job\Services\JobTemplateService;
use Exception;
use Illuminate\Http\Request;

class JobTemplateController extends Controller
{
    public function __construct(
        protected JobTemplateService $jobTemplateService
    ) {}

    /**
     * Get all job templates.
     */
    public function all(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'category_id', 'employer_id', 'search', 'status','template_type','sort_by',]);
            $result = $this->jobTemplateService->getAllTemplates($filters);

            return $this->successResponse(
                __('job::messages.job_templates.retrieved_successfully'),
                [
                    'templates' => JobTemplateResource::collection($result['data']),
                    'meta' => $result['meta'],
                ]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get job template by ID.
     */
    public function show(int $id)
    {
        try {
            $template = $this->jobTemplateService->getTemplateById($id);
            if (! $template) {
                return $this->errorResponse(__('job::messages.job_templates.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.job_templates.retrieved_successfully'),
                new JobTemplateResource($template)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Create job template.
     */
    public function create(JobTemplateRequest $request)
    {
        try {
            $template = $this->jobTemplateService->createTemplateForAdmin($request->validated());
            return $this->successResponse(
                __('job::messages.job_templates.created_successfully'),
                new JobTemplateResource($template),
                201
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update job template.
     * Optionally include "fields" in the body to replace all template fields (same shape as create).
     */
    public function update(JobTemplateRequest $request, int $id)
    {
        try {
            $template = $this->jobTemplateService->updateTemplateForAdmin($id, $request->validated());
            if (! $template) {
                return $this->errorResponse(__('job::messages.job_templates.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.job_templates.updated_successfully'),
                new JobTemplateResource($template)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Delete job template.
     */
    public function delete(int $id)
    {
        try {
            $deleted = $this->jobTemplateService->deleteTemplateForAdmin($id);
            if (! $deleted) {
                return $this->errorResponse(__('job::messages.job_templates.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.job_templates.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Toggle job template active status.
     */
    public function toggleStatus(int $id)
    {
        try {
            $template = $this->jobTemplateService->toggleTemplateStatus($id);
            if (! $template) {
                return $this->errorResponse(__('job::messages.job_templates.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.job_templates.status_updated_successfully'),
                new JobTemplateResource($template)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
