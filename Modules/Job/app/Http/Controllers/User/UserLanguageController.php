<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Http\Requests\User\Job\AddUserLanguageRequest;
use Modules\Job\Http\Requests\User\Job\UpdateUserLanguageRequest;
use Modules\Job\Http\Resources\User\Job\UserLanguageResource;
use Modules\Job\Services\UserLanguageService;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserLanguageController extends Controller
{
    public function __construct(
        protected UserLanguageService $service
    ) {}

    public function getMyLanguages()
    {
        try {
            $userId = Auth::id();
            $languages = $this->service->getMyLanguages($userId);

            return $this->successResponse(
                'Languages retrieved successfully',
                UserLanguageResource::collection($languages)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function addLanguage(AddUserLanguageRequest $request)
    {
        try {
            $userId = Auth::id();
            $items = $request->validated('items', []);
            $created = collect($items)->map(fn (array $data) => $this->service->addLanguage($userId, $data));

            return $this->successResponse(
                'Language(s) added successfully',
                UserLanguageResource::collection($created)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateLanguage(UpdateUserLanguageRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $language = $this->service->updateLanguage($userId, $id, $request->validated());

            return $this->successResponse(
                'Language updated successfully',
                new UserLanguageResource($language)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function removeLanguage(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->service->removeLanguage($userId, $id);

            if ($deleted) {
                return $this->successResponse('Language removed successfully');
            }

            return $this->errorResponse('Failed to remove language', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

