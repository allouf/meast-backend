<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\User\Job\AddUserProjectRequest;
use Modules\Job\Http\Requests\User\Job\UpdateUserProjectRequest;
use Modules\Job\Http\Resources\User\Job\UserProjectResource;
use Modules\Job\Services\UserProjectService;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserProjectController extends Controller
{
    public function __construct(
        protected UserProjectService $service
    ) {}

    public function getMyProjects()
    {
        try {
            $userId = Auth::id();
            $projects = $this->service->getMyProjects($userId);

            return $this->successResponse(
                'Projects retrieved successfully',
                UserProjectResource::collection($projects)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function addProject(AddUserProjectRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $created = collect($items)->map(fn (array $data) => $this->service->addProject($userId, $data));

            return $this->successResponse(
                'Project(s) added successfully',
                UserProjectResource::collection($created)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateProject(UpdateUserProjectRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $project = $this->service->updateProject($userId, $id, $request->validated());

            return $this->successResponse(
                'Project updated successfully',
                new UserProjectResource($project)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeProject(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->service->removeProject($userId, $id);

            if ($deleted) {
                return $this->successResponse('Project removed successfully');
            }

            return $this->errorResponse('Failed to remove project', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

