<?php

namespace Modules\Advertisement\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Advertisement\Http\Requests\User\AdvertisementCategoryRequest;
use Modules\Advertisement\Http\Resources\User\AdvertisementCategoryResource;
use Modules\Advertisement\Services\AdvertisementCategoryService;

class AdvertisementCategoryController extends Controller
{
    public function __construct(
        protected AdvertisementCategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'active_only']);
        $categories = $this->categoryService->getAllCategories($filters);
        if (is_array($categories) && isset($categories['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisement_categories.retrieved_successfully'), [
                'data' => AdvertisementCategoryResource::collection($categories['data']),
                'meta' => $categories['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_categories.retrieved_successfully'), AdvertisementCategoryResource::collection($categories));
    }

    public function active()
    {
        $categories = $this->categoryService->getActiveCategories();
        return $this->successResponse(__('advertisement::messages.advertisement_categories.active_retrieved_successfully'), AdvertisementCategoryResource::collection($categories));
    }

    public function withSubcategories(Request $request)
    {
        $filters = $request->only(['per_page']);
        $categories = $this->categoryService->getCategoriesWithSubcategories($filters);
        if (is_array($categories) && isset($categories['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisement_categories.with_subcategories_retrieved_successfully'), [
                'data' => AdvertisementCategoryResource::collection($categories['data']),
                'meta' => $categories['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_categories.with_subcategories_retrieved_successfully'), AdvertisementCategoryResource::collection($categories));
    }

    public function show($id)
    {
        $category = $this->categoryService->getCategoryBySlug($id);
        if (!$category) {
            $category = $this->categoryService->getCategoryById($id);
        }
        if (!$category) {
            return $this->errorResponse(__('advertisement::messages.advertisement_categories.not_found'), 404);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_categories.retrieved_successfully'), new AdvertisementCategoryResource($category));
    }

    public function store(AdvertisementCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_categories.created_successfully'), new AdvertisementCategoryResource($category), 201);
    }

    public function update(AdvertisementCategoryRequest $request, $id)
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        if (!$category) {
            return $this->errorResponse(__('advertisement::messages.advertisement_categories.not_found'), 404);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_categories.updated_successfully'), new AdvertisementCategoryResource($category));
    }

    public function destroy($id)
    {
        try {
            $this->categoryService->deleteCategory($id);
            return $this->successResponse(__('advertisement::messages.advertisement_categories.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
