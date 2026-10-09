<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Job\Http\Requests\User\Job\StoreJobTemplateRequest;
use Modules\Job\Http\Requests\User\Job\UpdateJobTemplateRequest;
use Modules\Job\Http\Resources\User\Job\JobTemplateResource;
use Modules\Job\Services\JobTemplateService;

class JobTemplateController extends Controller
{
    public function __construct(
        protected JobTemplateService $jobTemplateService
    ) {}

    public function myTemplates(Request $request)
    {
        try {
            $userId = Auth::id();
            $filters = $request->only(['per_page', 'category_id', 'active_only', 'paginate','search']);
            $filters['paginate'] = filter_var($filters['paginate'] ?? true, FILTER_VALIDATE_BOOLEAN);

            $result = $this->jobTemplateService->getMyTemplates($userId, $filters);
            if (isset($result['data'], $result['meta'])) {

                return $this->successResponse(__('job::messages.job_templates.retrieved_successfully'), ['templates' => JobTemplateResource::collection($result['data']), 'meta' => $result['meta']]);
            }

            return $this->successResponse(
                __('job::messages.job_templates.retrieved_successfully'),
                ['templates' => JobTemplateResource::collection($result)]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function systemTemplates(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'category_id']);
            $result = $this->jobTemplateService->getSystemTemplates($filters);
            return $this->successResponse(__('job::messages.job_templates.retrieved_successfully'), ['templates' => JobTemplateResource::collection($result['data']), 'meta' => $result['meta']]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function availableByCategory(Request $request, int $categoryId)
    {
        try {
            $userId = Auth::id();
            $filters = $request->only(['per_page']);
            $result = $this->jobTemplateService->getAvailableByCategory($categoryId, $userId, $filters);
            return $this->successResponse(__('job::messages.job_templates.retrieved_successfully'), ['templates' => JobTemplateResource::collection($result['data']), 'meta' => $result['meta']]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $id)
    {
        try {
            $userId = Auth::id();
            $template = $this->jobTemplateService->getTemplateForUser($id, $userId);
            if (! $template) {
                return $this->errorResponse(__('job::messages.job_templates.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.job_templates.retrieved_successfully'), new JobTemplateResource($template));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function store(StoreJobTemplateRequest $request)
    {
        try {
            $userId = Auth::id();
            $template = $this->jobTemplateService->createTemplate($userId, $request->validated());
            return $this->successResponse(__('job::messages.job_templates.created_successfully'), new JobTemplateResource($template), 201);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(UpdateJobTemplateRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $template = $this->jobTemplateService->updateTemplate($id, $userId, $request->validated());
            if (! $template) {
                return $this->errorResponse(__('job::messages.job_templates.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.job_templates.updated_successfully'), new JobTemplateResource($template));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->jobTemplateService->deleteTemplate($id, $userId);
            if (! $deleted) {
                return $this->errorResponse(__('job::messages.job_templates.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.job_templates.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function cloneFromSystem(int $id)
    {
        try {
            $userId = Auth::id();
            $template = $this->jobTemplateService->cloneFromSystem($id, $userId);
            return $this->successResponse(__('job::messages.job_templates.cloned_successfully'), new JobTemplateResource($template), 201);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
