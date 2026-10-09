<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\Seller\ProductRequest;
use Modules\Store\Models\ProductGallery;
use Modules\Store\Models\ProductVariant;
use Modules\Store\Http\Resources\User\ProductDetailsResource;
use Modules\Store\Http\Resources\User\ProductListResource;
use Modules\Store\Http\Resources\User\ProductVariantResource;
use Modules\Store\Http\Resources\User\ProductWithVariantsResource;
use Modules\Store\Models\Product;
use Modules\Store\Services\ProductService;
use Modules\Store\Services\StoreService;
use Modules\Store\Services\StoreMemberPermissionService;
use Illuminate\Http\Request;
use Exception;
use Modules\Store\Http\Requests\Seller\UpdateGallerySortRequest;
use Modules\Store\Http\Requests\Seller\UpdateVariantRequest;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected StoreService $storeService,
        protected StoreMemberPermissionService $permissionService
    ) {}
    /**
     * Authorize product operation using store member permissions
     */
    private function authorizeProductOperation(string $permission, Product $product): void
    {
        $this->permissionService->requirePermission($product->store_id, $permission);
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

            $this->permissionService->requirePermission($storeId, 'products.read');
            $products = $this->productService->getByStore($storeId, $filters);
            return $this->successResponse('Products retrieved successfully', [
                'products' => ProductListResource::collection($products['data']),
                'meta' => $products['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function getByStoreWithVariants($storeId)
    {
        try {
            $this->permissionService->requirePermission($storeId, 'products.read');
            $products = $this->productService->getByStoreWithVariants($storeId);
            return $this->successResponse('Products with variants retrieved successfully', [
                'products' => ProductWithVariantsResource::collection($products['data']),
                'meta' => $products['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function show($id)
    {
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }
            $this->authorizeProductOperation('products.read', $product);
            return $this->successResponse('Product retrieved successfully', new ProductDetailsResource($product));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function create(ProductRequest $request)
    {
        $data = $request->validated();
        try {
            $this->permissionService->requirePermission($data['store_id'], 'products.create');
            $product = $this->productService->create($data);
            return $this->successResponse('Product created successfully', new ProductDetailsResource($product));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function update(ProductRequest $request, $id)
    {
        $data = $request->validated();
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }
            $this->authorizeProductOperation('products.update', $product);
            $product = $this->productService->update($data, $id);
            return $this->successResponse('Product updated successfully', new ProductDetailsResource($product));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function delete($id)
    {
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }
            $this->authorizeProductOperation('products.delete', $product);
            $this->productService->delete($id);
            return $this->successResponse('Product deleted successfully');
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function switchActivation($id)
    {
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }
            $this->authorizeProductOperation('products.update', $product);
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
    public function duplicate($id)
    {
        try {
            $product = $this->productService->findById($id);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }

            $this->authorizeProductOperation('products.create', $product);
            $duplicatedProduct = $this->productService->duplicate($id);
            return $this->successResponse('Product duplicated successfully', new ProductDetailsResource($duplicatedProduct));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function deleteGalleryItem($galleryItemId)
    {
        try {

            $galleryItem = ProductGallery::find($galleryItemId);
            if (!$galleryItem) {
                return $this->errorResponse('Gallery item not found.', 404);
            }

            $product = $this->productService->findById($galleryItem->product_id);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }

            $this->authorizeProductOperation('products.update', $product);

            $deleted = $this->productService->deleteGalleryItem($galleryItemId);
            if ($deleted) {
                return $this->successResponse('Gallery item deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete gallery item.', 500);
            }
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function updateGallerySort(UpdateGallerySortRequest $request, $productId)
    {
        try {
            $product = $this->productService->findById($productId);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }

            $this->authorizeProductOperation('products.update', $product);
            $gallerySortData = $request->validated()['gallery'];
            $galleryIds = array_column($gallerySortData, 'id');
            $isValid = $this->productService->validateGalleryItems($productId, $galleryIds);
            if (!$isValid) {
                return $this->errorResponse('One or more gallery items do not exist or do not belong to this product.', 400);
            }

            $updated = $this->productService->updateGallerySort($productId, $gallerySortData);
            if ($updated) {
                return $this->successResponse('Gallery sort order updated successfully');
            } else {
                return $this->errorResponse('Failed to update gallery sort order.', 500);
            }
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function updateVariant(UpdateVariantRequest $request, $variantId)
    {
        try {
            $data = $request->validated();

            $variant = ProductVariant::with('product')->findOrFail($variantId);
            $product = $variant->product;

            $this->authorizeProductOperation('products.update', $product);

            $updatedVariant = $this->productService->updateVariant($variantId, $data);
            return $this->successResponse('Variant updated successfully', new ProductVariantResource($updatedVariant));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getRelatedProducts(Request $request, $productId)
    {
        $search = $request->query('search');
        $products = $this->productService->getRelatedProducts($productId, $search);
        return $this->successResponse('success', ProductListResource::collection($products));
    }

    public function getProductVariants($productId)
    {
        $variants = $this->productService->getVariantsByProduct($productId);
        return $this->successResponse('success', $variants);
    }
}
