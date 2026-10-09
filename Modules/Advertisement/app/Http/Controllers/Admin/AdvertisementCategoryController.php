<?php

namespace Modules\Advertisement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Advertisement\Http\Requests\Admin\AdvertisementCategoryRequest;
use Modules\Advertisement\Http\Requests\Admin\UpdateAdvertisementCategoryRequest;
use Modules\Advertisement\Http\Resources\Admin\AdvertisementCategoryResource;
use Modules\Advertisement\Services\AdvertisementCategoryService;

class AdvertisementCategoryController extends Controller
{
    public function __construct(
        protected AdvertisementCategoryService $categoryService
    ) {}

    public function all(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'active_only', 'search']);
            $categories = $this->categoryService->getAllCategories($filters);
            if (is_array($categories) && isset($categories['data'])) {
                return $this->successResponse(__('advertisement::messages.advertisement_categories.retrieved_successfully'), [
                    'data' => AdvertisementCategoryResource::collection($categories['data']),
                    'meta' => $categories['meta']
                ]);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_categories.retrieved_successfully'), AdvertisementCategoryResource::collection($categories));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show($id)
    {
        try {
            $category = $this->categoryService->getCategoryById($id);
            if (!$category) {
                return $this->errorResponse(__('advertisement::messages.advertisement_categories.not_found'), 404);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_categories.retrieved_successfully'), new AdvertisementCategoryResource($category->load(['subcategories', 'advertisements'])));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function create(AdvertisementCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());
            return $this->successResponse(__('advertisement::messages.advertisement_categories.created_successfully'), new AdvertisementCategoryResource($category), 201);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(UpdateAdvertisementCategoryRequest $request, $id)
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());
            if (!$category) {
                return $this->errorResponse(__('advertisement::messages.advertisement_categories.not_found'), 404);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_categories.updated_successfully'), new AdvertisementCategoryResource($category));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function delete($id)
    {
        try {
            $this->categoryService->deleteCategory($id);
            return $this->successResponse(__('advertisement::messages.advertisement_categories.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $category = $this->categoryService->toggleCategoryStatus($id);
            return $this->successResponse(__('advertisement::messages.advertisement_categories.status_updated_successfully'), new AdvertisementCategoryResource($category));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
