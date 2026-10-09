<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Services\Http\Resources\User\ServiceCategoryOptionResource;
use Modules\Services\Services\ServiceCategoryService;

class ServiceCategoryController extends Controller
{
    public function __construct(
        protected ServiceCategoryService $serviceCategoryService
    ) {}

    /**
     * List active service categories (for user to select when browsing services).
     */
    public function index()
    {
        $categories = $this->serviceCategoryService->getActiveOrdered();

        return $this->successResponse(
            __('services::messages.service_categories.retrieved_successfully'),
            ServiceCategoryOptionResource::collection($categories)
        );
    }
}
