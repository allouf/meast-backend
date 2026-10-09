<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\User\CreatePackageRequest;
use Modules\Store\Http\Requests\User\UpdatePackageRequest;
use Modules\Store\Http\Requests\User\AttachPackageItemsRequest;
use Modules\Store\Http\Requests\User\DetachPackageItemsRequest;
use Modules\Store\Http\Resources\User\PackageResource;
use Modules\Store\Services\PackageService;
use Exception;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(protected PackageService $packageService) {}

    /**
     * Get all packages for a product
     * GET /products/{id}/bundles
     */
    public function index(Request $request, int $productId)
    {
        try {
            $activeOnly = $request->boolean('active_only', false);
            $packages = $this->packageService->getByProduct($productId, $activeOnly);
            
            return $this->successResponse('Packages retrieved successfully', [
                'packages' => PackageResource::collection($packages->load([
                    'store', 
                    'product.store', 
                    'product.options.values',
                    'product.variants.optionValues',
                    'items.product', 
                    'items.product.options.values',
                    'items.product.variants.optionValues',
                    'items.variant',
                    'items.variant.optionValues'
                ]))
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Get all packages for a store
     * GET /stores/{storeId}/bundles
     */
    public function getByStore(Request $request, int $storeId)
    {
        try {
            $activeOnly = $request->boolean('active_only', false);
            $packages = $this->packageService->getByStore($storeId, $activeOnly);
            
            return $this->successResponse('Packages retrieved successfully', [
                'packages' => PackageResource::collection($packages->load([
                    'store', 
                    'product.store',
                    'product.options.values',
                    'product.variants.optionValues',
                    'items.product', 
                    'items.product.options.values',
                    'items.product.variants.optionValues',
                    'items.variant',
                    'items.variant.optionValues'
                ]))
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Create a new package for a product
     * POST /products/{id}/packages
     */
    public function store(CreatePackageRequest $request, int $productId)
    {
        try {
            $data = $request->validated();
            $package = $this->packageService->create($productId, $data);
            
            return $this->successResponse('Package created successfully', [
                'package' => new PackageResource($package)
            ], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get a specific package
     * GET /packages/{id}
     */
    public function show(int $id)
    {
        try {
            $package = $this->packageService->findById($id);
            
            return $this->successResponse('Package retrieved successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Update a package
     * PUT /packages/{id}
     */
    public function update(UpdatePackageRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $package = $this->packageService->update($id, $data);
            
            return $this->successResponse('Package updated successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Delete a package
     * DELETE /packages/{id}
     */
    public function destroy(int $id)
    {
        try {
            $this->packageService->delete($id);
            
            return $this->successResponse('Package deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Attach items to a package
     * POST /packages/{id}/items/attach
     */
    public function attachItems(AttachPackageItemsRequest $request, int $id)
    {
        try {
            $items = $request->validated()['items'];
            $package = $this->packageService->attachItems($id, $items);
            
            return $this->successResponse('Items attached successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Detach items from a package
     * POST /packages/{id}/items/detach
     */
    public function detachItems(DetachPackageItemsRequest $request, int $id)
    {
        try {
            $itemIds = $request->validated()['item_ids'];
            $package = $this->packageService->detachItems($id, $itemIds);
            
            return $this->successResponse('Items detached successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
