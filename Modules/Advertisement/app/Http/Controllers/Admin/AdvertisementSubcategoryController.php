<?php

namespace Modules\Advertisement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Advertisement\Http\Requests\Admin\AdvertisementSubcategoryRequest;
use Modules\Advertisement\Http\Resources\Admin\AdvertisementSubcategoryResource;
use Modules\Advertisement\Services\AdvertisementSubcategoryService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertisementSubcategoryController extends Controller
{
    public function __construct(
        protected AdvertisementSubcategoryService $subcategoryService
    ) {}

    public function all(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'active_only', 'category_id', 'search']);
            $subcategories = $this->subcategoryService->getAllSubcategories($filters);
            if (is_array($subcategories) && isset($subcategories['data'])) {
                return $this->successResponse(__('advertisement::messages.advertisement_subcategories.retrieved_successfully'), [
                    'data' => AdvertisementSubcategoryResource::collection($subcategories['data']),
                    'meta' => $subcategories['meta']
                ]);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.retrieved_successfully'), AdvertisementSubcategoryResource::collection($subcategories));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show($id)
    {
        try {
            $subcategory = $this->subcategoryService->getSubcategoryById($id);
            if (!$subcategory) {
                return $this->errorResponse(__('advertisement::messages.advertisement_subcategories.not_found'), 404);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.retrieved_successfully'), new AdvertisementSubcategoryResource($subcategory->load(['category', 'advertisements'])));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function create(AdvertisementSubcategoryRequest $request)
    {
        try {
            $subcategory = $this->subcategoryService->createSubcategory($request->validated());
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.created_successfully'), new AdvertisementSubcategoryResource($subcategory), 201);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(AdvertisementSubcategoryRequest $request, $id)
    {
        try {
            $subcategory = $this->subcategoryService->updateSubcategory($id, $request->validated());
            if (!$subcategory) {
                return $this->errorResponse(__('advertisement::messages.advertisement_subcategories.not_found'), 404);
            }
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.updated_successfully'), new AdvertisementSubcategoryResource($subcategory));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function delete($id)
    {
        try {
            $this->subcategoryService->deleteSubcategory($id);
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.deleted_successfully'), null);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $subcategory = $this->subcategoryService->toggleStatus($id);
            return $this->successResponse(__('advertisement::messages.advertisement_subcategories.status_toggled_successfully'), new AdvertisementSubcategoryResource($subcategory));
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
