<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\Admin\PackageResource;
use Modules\Store\Services\PackageService;
use Modules\Store\Services\ProductService;
use Exception;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(
        protected PackageService $packageService,
        protected ProductService $productService,
    ) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'store_id'   => 'nullable|integer|exists:stores,id',
                'product_id' => 'nullable|integer|exists:products,id',
                'active_only' => 'nullable|boolean',
                'paginate'   => 'nullable|boolean',
            ]);

            // if ($request->filled('store_id')) {
            //     $this->permissionService
            //         ->requirePermission($request->store_id, 'products.read');
            // }

            $packages = $this->packageService->index([
                'store_id'   => $request->store_id,
                'product_id' => $request->product_id,
                'active_only' => $request->boolean('active_only'),
                'paginate'   => $request->boolean('paginate', true),
            ]);

            $response = [
                'packages' => PackageResource::collection(
                    $packages['data'] ?? $packages
                ),
            ];

            if (!empty($packages['meta'])) {
                $response['meta'] = $packages['meta'];
            }

            return $this->successResponse(__('store::messages.admin.packages_retrieved_successfully'), $response);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $id)
    {
        try {
            $package = $this->packageService->findById($id);
            return $this->successResponse(__('store::messages.admin.package_retrieved_successfully'), [
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
            $this->packageService->delete($id);
            return $this->successResponse(__('store::messages.admin.package_deleted_successfully'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function toggleActivation(int $packageId)
    {
        try {
            $package = $this->packageService->findById($packageId);
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
