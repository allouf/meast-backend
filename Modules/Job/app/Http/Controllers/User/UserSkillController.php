<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\User\Job\AddUserSkillRequest;
use Modules\Job\Http\Requests\User\Job\UpdateUserSkillRequest;
use Modules\Job\Http\Resources\User\Job\UserSkillResource;
use Modules\Job\Services\UserSkillService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Job\Http\Resources\User\Job\SkillResource;
use Modules\Job\Services\SkillService;

class UserSkillController extends Controller
{
    public function __construct(
        protected UserSkillService $service,
        protected SkillService $skillService
    ) {}

    public function getMySkills()
    {
        try {
            $userId = Auth::id();
            $skills = $this->service->getMySkills($userId);

            return $this->successResponse(
                'Skills retrieved successfully',
                UserSkillResource::collection($skills)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getAvailableSkills(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 10);

            $filters = [
                'is_active' => true,
                'per_page' => $perPage,
                'search'    => $request->query('search'),

            ];

            $skills = $this->skillService->getAll($filters);

            // $skillsCollection = collect($paginated['data']);

            return $this->successResponse(
                'Available skills retrieved successfully',
                [
                    'skills' => SkillResource::collection($skills['data']),
                    'meta' => $skills['meta']
                ]
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }


    public function addSkill(AddUserSkillRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $created = collect($items)->map(fn(array $data) => $this->service->addSkill($userId, $data));

            return $this->successResponse(
                'Skill(s) added successfully',
                UserSkillResource::collection($created)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateSkill(UpdateUserSkillRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $skillIds = collect($items)->pluck('skill_id')->unique()->values()->all();
            $synced = $this->service->syncSkills($userId, $skillIds);

            return $this->successResponse(
                'Skill(s) updated successfully',
                UserSkillResource::collection($synced)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeSkill(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->service->removeSkill($userId, $id);

            if ($deleted) {
                return $this->successResponse('Skill removed successfully');
            }

            return $this->errorResponse('Failed to remove skill', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
