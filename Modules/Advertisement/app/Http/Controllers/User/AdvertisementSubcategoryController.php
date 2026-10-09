<?php

namespace Modules\Advertisement\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Advertisement\Http\Requests\User\AdvertisementSubcategoryRequest;
use Modules\Advertisement\Http\Resources\User\AdvertisementSubcategoryResource;
use Modules\Advertisement\Services\AdvertisementSubcategoryService;

class AdvertisementSubcategoryController extends Controller
{
    public function __construct(
        protected AdvertisementSubcategoryService $subcategoryService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'active_only', 'category_id']);
        $subcategories = $this->subcategoryService->getAllSubcategories($filters);
        if (is_array($subcategories) && isset($subcategories['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.retrieved_successfully'), [
                'data' => AdvertisementSubcategoryResource::collection($subcategories['data']),
                'meta' => $subcategories['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_subcategories.retrieved_successfully'), AdvertisementSubcategoryResource::collection($subcategories));
    }

    public function active(Request $request)
    {
        $filters = $request->only(['per_page']);
        $subcategories = $this->subcategoryService->getActiveSubcategories($filters);
        if (is_array($subcategories) && isset($subcategories['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.active_retrieved_successfully'), [
                'data' => AdvertisementSubcategoryResource::collection($subcategories['data']),
                'meta' => $subcategories['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_subcategories.active_retrieved_successfully'), AdvertisementSubcategoryResource::collection($subcategories));
    }

    public function byCategory($categoryId)
    {
        $subcategories = $this->subcategoryService->getSubcategoriesByCategory($categoryId);
        return $this->successResponse(__('advertisement::messages.advertisement_subcategories.by_category_retrieved_successfully'), AdvertisementSubcategoryResource::collection($subcategories));
    }

    public function show($id)
    {
        $subcategory = $this->subcategoryService->getSubcategoryBySlug($id);
        if (!$subcategory) {
            $subcategory = $this->subcategoryService->getSubcategoryById($id);
        }
        if (!$subcategory) {
            return $this->errorResponse(__('advertisement::messages.advertisement_subcategories.not_found'), 404);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_subcategories.retrieved_successfully'), new AdvertisementSubcategoryResource($subcategory));
    }

    public function store(AdvertisementSubcategoryRequest $request)
    {
        try {
            $subcategory = $this->subcategoryService->createSubcategory($request->validated());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_subcategories.created_successfully'), new AdvertisementSubcategoryResource($subcategory), 201);
    }

    public function update(AdvertisementSubcategoryRequest $request, $id)
    {
        try {
            $subcategory = $this->subcategoryService->updateSubcategory($id, $request->validated());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        if (!$subcategory) {
            return $this->errorResponse(__('advertisement::messages.advertisement_subcategories.not_found'), 404);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_subcategories.updated_successfully'), new AdvertisementSubcategoryResource($subcategory));
    }

    public function destroy($id)
    {
        try {
            $this->subcategoryService->deleteSubcategory($id);
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
