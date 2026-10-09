<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\User\Job\AddUserLinkRequest;
use Modules\Job\Http\Requests\User\Job\UpdateUserLinkRequest;
use Modules\Job\Http\Resources\User\Job\UserLinkResource;
use Modules\Job\Services\UserLinkService;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserLinkController extends Controller
{
    public function __construct(
        protected UserLinkService $service
    ) {}

    public function getMyLinks()
    {
        try {
            $userId = Auth::id();
            $links = $this->service->getMyLinks($userId);

            return $this->successResponse(
                'Links retrieved successfully',
                UserLinkResource::collection($links)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function addLink(AddUserLinkRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $created = collect($items)->map(fn (array $data) => $this->service->addLink($userId, $data));

            return $this->successResponse(
                'Link(s) added successfully',
                UserLinkResource::collection($created)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateLink(UpdateUserLinkRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $link = $this->service->updateLink($userId, $id, $request->validated());

            return $this->successResponse(
                'Link updated successfully',
                new UserLinkResource($link)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeLink(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->service->removeLink($userId, $id);

            if ($deleted) {
                return $this->successResponse('Link removed successfully');
            }

            return $this->errorResponse('Failed to remove link', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

