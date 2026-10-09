<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Modules\Services\Http\Resources\Provider\ServiceCategoryOptionResource;
use Modules\Services\Services\ServiceCategoryService;

class ServiceCategoryController extends Controller
{
    public function __construct(
        protected ServiceCategoryService $serviceCategoryService
    ) {}

    /**
     * List active service categories (for provider to select when creating offer).
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
