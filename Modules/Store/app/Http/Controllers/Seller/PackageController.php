<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\Seller\AttachPackageItemsRequest;
use Modules\Store\Http\Requests\Seller\CreatePackageRequest;
use Modules\Store\Http\Requests\Seller\DetachPackageItemsRequest;
use Modules\Store\Http\Requests\Seller\UpdatePackageRequest;
use Modules\Store\Http\Resources\User\PackageResource;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductPackage;
use Modules\Store\Services\PackageService;
use Modules\Store\Services\ProductService;
use Modules\Store\Services\StoreMemberPermissionService;
use Exception;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(
        protected PackageService $packageService,
        protected ProductService $productService,
        protected StoreMemberPermissionService $permissionService
    ) {}

    private function authorizePackageOperation(string $permission, ProductPackage $package): void
    {
        $product = $package->product;
        $this->permissionService->requirePermission($product->store_id, $permission);
    }

    private function authorizeProductOperation(string $permission, Product $product): void
    {
        $this->permissionService->requirePermission($product->store_id, $permission);
    }

    public function index(Request $request, int $productId)
    {
        try {
            $product = $this->productService->findById($productId);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }
            $this->authorizeProductOperation('products.read', $product);
            $activeOnly = $request->boolean('active_only', false);
            $packages = $this->packageService->getByProduct($productId, $activeOnly);
            return $this->successResponse('Packages retrieved successfully', [
                'packages' => PackageResource::collection($packages->load('store', 'product.store', 'items.product', 'items.variant'))
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getByStore(Request $request, int $storeId)
    {
        try {
            $this->permissionService->requirePermission($storeId, 'products.read');

            $activeOnly = $request->boolean('active_only', false);
            $paginate = $request->boolean('paginate', true);

            $packages = $this->packageService->getByStore($storeId, $activeOnly, $paginate);

            $response = [
                'packages' => PackageResource::collection($packages['data'] ?? $packages),
            ];
            if (!empty($packages['meta'])) {
                $response['meta'] = $packages['meta'];
            }
            return $this->successResponse('Packages retrieved successfully', $response);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }


    public function store(CreatePackageRequest $request, int $productId)
    {
        try {
            $product = $this->productService->findById($productId);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }
            $this->authorizeProductOperation('products.update', $product);
            $data = $request->validated();
            $package = $this->packageService->create($productId, $data);
            return $this->successResponse('Package created successfully', [
                'package' => new PackageResource($package)
            ], 201);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $id)
    {
        try {
            $package = $this->packageService->findById($id);
            $this->authorizePackageOperation('products.read', $package);
            return $this->successResponse('Package retrieved successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(UpdatePackageRequest $request, int $id)
    {
        try {
            $package = $this->packageService->findById($id);
            $this->authorizePackageOperation('products.update', $package);
            $data = $request->validated();
            $package = $this->packageService->update($id, $data);
            return $this->successResponse('Package updated successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $package = $this->packageService->findById($id);
            $this->authorizePackageOperation('products.delete', $package);
            $this->packageService->delete($id);
            return $this->successResponse('Package deleted successfully');
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function attachItems(AttachPackageItemsRequest $request, int $id)
    {
        try {
            $package = $this->packageService->findById($id);
         
            $this->authorizePackageOperation('products.update', $package);
            $items = $request->validated()['items'];
            $package = $this->packageService->attachItems($id, $items);
            return $this->successResponse('Items attached successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function detachItems(DetachPackageItemsRequest $request, int $id)
    {
        try {
            $package = $this->packageService->findById($id);
        
            $this->authorizePackageOperation('products.update', $package);
            $itemIds = $request->validated()['item_ids'];
            $package = $this->packageService->detachItems($id, $itemIds);
            return $this->successResponse('Items detached successfully', [
                'package' => new PackageResource($package)
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }


    public function toggleActivation(int $packageId)
    {
        try {
            $package = $this->packageService->findById($packageId);

            $this->authorizePackageOperation('products.update', $package);

            $updatedPackage = $this->packageService->toggleActivation($packageId);

            return $this->successResponse(
                'Package activation status updated successfully',
                [
                    'package' => new PackageResource($updatedPackage)
                ]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
