<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Services\Http\Requests\Admin\ServiceCategoryRequest;
use Modules\Services\Http\Resources\Admin\ServiceCategoryResource;
use Modules\Services\Services\ServiceCategoryService;

class ServiceCategoryController extends Controller
{
    public function __construct(
        protected ServiceCategoryService $serviceCategoryService
    ) {}

    /**
     * Get all service categories (paginated).
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'search', 'is_active']);
            $result = $this->serviceCategoryService->getAll($filters);
            return $this->successResponse(
                __('services::messages.service_categories.retrieved_successfully'),
                [
                    'categories' => ServiceCategoryResource::collection($result->items()),
                    'meta' => [
                        'current_page' => $result->currentPage(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'total' => $result->total(),
                    ],
                ]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get service category by ID.
     */
    public function show(int $id)
    {
        try {
            $category = $this->serviceCategoryService->getById($id);
            if (! $category) {
                return $this->errorResponse(__('services::messages.service_categories.not_found'), 404);
            }
            return $this->successResponse(
                __('services::messages.service_categories.retrieved_successfully'),
                new ServiceCategoryResource($category)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Create service category.
     */
    public function store(ServiceCategoryRequest $request)
    {
        try {
            $data = $request->validated();
            unset($data['slug']); // Slug is generated from name in ServiceCategory model
            $category = $this->serviceCategoryService->create($data);
            return $this->successResponse(
                __('services::messages.service_categories.created_successfully'),
                new ServiceCategoryResource($category),
                201
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update service category.
     */
    public function update(ServiceCategoryRequest $request, int $id)
    {
        try {
            $category = $this->serviceCategoryService->update($id, $request->validated());
            if (! $category) {
                return $this->errorResponse(__('services::messages.service_categories.not_found'), 404);
            }
            return $this->successResponse(
                __('services::messages.service_categories.updated_successfully'),
                new ServiceCategoryResource($category)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Delete service category.
     */
    public function destroy(int $id)
    {
        try {
            $this->serviceCategoryService->delete($id);
            return $this->successResponse(__('services::messages.service_categories.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Toggle service category active status.
     */
    public function toggleStatus(int $id)
    {
        try {
            $category = $this->serviceCategoryService->toggleStatus($id);
            return $this->successResponse(
                __('services::messages.service_categories.status_updated_successfully'),
                new ServiceCategoryResource($category)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
