<?php

namespace Modules\Job\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Job\Http\Requests\Admin\Job\SkillRequest;
use Modules\Job\Http\Resources\Admin\Job\SkillResource;
use Modules\Job\Services\SkillService;

class SkillController extends Controller
{
    public function __construct(
        protected SkillService $skillService
    ) {}

    /**
     * Get all skills.
     */
    public function all(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'status', 'search', 'sort_by']);
            $result = $this->skillService->getAll($filters);

            if (isset($result['data'], $result['meta'])) {
                return $this->successResponse(
                    __('job::messages.skills.retrieved_successfully'),
                    [
                        'skills' => SkillResource::collection($result['data']),
                        'meta' => $result['meta'],
                    ]
                );
            }

            return $this->successResponse(
                __('job::messages.skills.retrieved_successfully'),
                SkillResource::collection($result)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get skill by ID.
     */
    public function show(int $id)
    {
        try {
            $skill = $this->skillService->getById($id);
            if (! $skill) {
                return $this->errorResponse(__('job::messages.skills.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.skills.retrieved_successfully'),
                new SkillResource($skill)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Create skill.
     */
    public function create(SkillRequest $request)
    {
        try {
            $skill = $this->skillService->create($request->validated());
            return $this->successResponse(
                __('job::messages.skills.created_successfully'),
                new SkillResource($skill),
                201
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update skill.
     */
    public function update(SkillRequest $request, int $id)
    {
        try {
            $skill = $this->skillService->update($id, $request->validated());
            if (! $skill) {
                return $this->errorResponse(__('job::messages.skills.not_found'), 404);
            }
            return $this->successResponse(
                __('job::messages.skills.updated_successfully'),
                new SkillResource($skill)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Delete skill.
     */
    public function delete(int $id)
    {
        try {
            $this->skillService->delete($id);
            return $this->successResponse(__('job::messages.skills.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Toggle skill active status.
     */
    public function toggleStatus(int $id)
    {
        try {
            $skill = $this->skillService->toggleStatus($id);
            return $this->successResponse(
                __('job::messages.skills.status_updated_successfully'),
                new SkillResource($skill)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
