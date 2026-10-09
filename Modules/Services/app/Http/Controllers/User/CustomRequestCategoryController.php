<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Services\Http\Resources\User\CustomRequestCategoryResource;
use Modules\Services\Services\CustomRequestService;

class CustomRequestCategoryController extends Controller
{
    public function __construct(
        protected CustomRequestService $customRequestService
    ) {}

    public function index()
    {
        $categories = $this->customRequestService->listCategories();

        return $this->successResponse(
            __('services::messages.custom_request_categories.retrieved_successfully'),
            CustomRequestCategoryResource::collection($categories)
        );
    }
}
