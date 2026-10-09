<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\Admin\AdminProductRequest;
use Modules\Store\Http\Resources\Admin\ProductDetailsResource;
use Modules\Store\Http\Resources\Admin\ProductListResource;
use Modules\Store\Services\ProductService;
use Exception;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'search' => 'nullable|max:255',
                'store_id' => 'nullable|integer|exists:stores,id',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'required|integer|exists:categories,id',
                'status' => 'nullable|in:draft,active,inactive,sold_out,archived',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|gte:min_price',
                'has_discount' => 'nullable|boolean',
            ]);

            $filters = [
                'search' => $request->input('search'),
                'store_id' => $request->input('store_id'),
                'category_ids' => $request->input('category_ids', []),
                'status' => $request->input('status'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'has_discount' => $request->input('has_discount'),
            ];

            $products = $this->productService->index($filters);

            return $this->successResponse(__('store::messages.admin.products_retrieved_successfully'), [
                'products' => ProductListResource::collection($products['data']),
                'meta' => $products['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    
    public function getByStore(Request $request, $storeId)
    {
        try {
            $request->validate([
                'search' => 'nullable|max:255',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'required|integer|exists:categories,id',
                'status' => 'nullable|in:draft,active,inactive,sold_out,archived',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|gte:min_price',
                'has_discount' => 'nullable|boolean',
            ]);

            $filters = [
                'search' => $request->input('search'),
                'category_ids' => $request->input('category_ids', []),
                'status' => $request->input('status'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'has_discount' => $request->input('has_discount'),
            ];

            $products = $this->productService->getByStore($storeId, $filters);
            return $this->successResponse(__('store::messages.admin.products_retrieved_successfully'), [
                'products' => ProductListResource::collection($products['data']),
                'meta' => $products['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function store(AdminProductRequest $request)
    {
        try {
            $product = $this->productService->create($request->validated());

            return $this->successResponse(
                'Product created successfully',
                new ProductDetailsResource($product)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(AdminProductRequest $request, int $id)
    {
        try {
            $product = $this->productService->findById($id);

            if (!$product) {
                return $this->errorResponse(__('store::messages.admin.product_not_found'), 404);
            }

            $product = $this->productService->update(
                $request->validated(),
                $id
            );

            return $this->successResponse(
                'Product updated successfully',
                new ProductDetailsResource($product)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function delete($id)
    {
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse(__('store::messages.admin.product_not_found'), 404);
            }
            $this->productService->delete($id);
            return $this->successResponse(__('store::messages.admin.product_deleted_successfully'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function switchActivation($id)
    {
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse(__('store::messages.admin.product_not_found'), 404);
            }
            $toggledProduct = $this->productService->switchActivation($id);
            $status = $toggledProduct->status->value;
            $message = $status === 'active'
                ? 'Product has been activated successfully.'
                : 'Product has been deactivated successfully.';
            return $this->successResponse($message, new ProductDetailsResource($toggledProduct));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show($id)
    {
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse(__('store::messages.admin.product_not_found'), 404);
            }
            return $this->successResponse(__('store::messages.admin.product_retrieved_successfully'), new ProductDetailsResource($product));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
