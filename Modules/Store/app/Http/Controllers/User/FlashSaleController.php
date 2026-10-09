<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\User\FlashSaleRequest;
use Modules\Store\Http\Resources\User\FlashSaleListResource;
use Modules\Store\Http\Resources\User\FlashSaleResource;
use Modules\Store\Models\Product;
use Modules\Store\Services\FlashSaleService;
use Modules\Store\Services\StoreMemberPermissionService;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function __construct(
        protected FlashSaleService $flashSaleService,
        protected StoreMemberPermissionService $permissionService
    ) {}
    private function authorizeProductOperation(string $permission, Product $product): void
    {
        $this->permissionService->requirePermission($product->store_id, $permission);
    }
    public function store(FlashSaleRequest $request)
    {
        $data = $request->validated();
        foreach ($data['products'] as $productData) {
            $product = Product::findOrFail($productData['product_id']);
            $this->authorizeProductOperation('products.pricing', $product);
        }
        $flashSale = $this->flashSaleService->createFlashSale($request->validated());
        return $this->successResponse(__('store::messages.Flash Sale created successfully'), new FlashSaleResource($flashSale));
    }
    public function update(FlashSaleRequest $request, $id)
    {
        $data = $request->validated();
        $flashSale = $this->flashSaleService->findById($id);
        // التحقق من الصلاحيات
        if (isset($data['products'])) {
            foreach ($data['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $this->authorizeProductOperation('products.pricing', $product);
            }
        }
        // التحديث ضمن transaction
        $updatedFlashSale = $this->flashSaleService->updateFlashSale($data, $flashSale);
        return $this->successResponse(__('store::messages.Flash Sale updated successfully'), new FlashSaleResource($updatedFlashSale));
    }
    public function destroy($id)
    {
        // جلب العرض للتحقق من وجوده
        $flashSale = $this->flashSaleService->findById($id);
        // التحقق من صلاحيات التعديل على المنتجات المرتبطة قبل الحذف (اختياري)
        foreach ($flashSale->variants as $variant) {
            $this->authorizeProductOperation('products.pricing', $variant->product);
        }
        // تنفيذ الحذف
        $this->flashSaleService->delete($id);
        return $this->successResponse(__('store::messages.Flash Sale deleted successfully'));
    }
    public function getByStore($storeId, Request $request)
    {
        $flashSales = $this->flashSaleService->getFlashSalesByStore(
            $storeId,
            true,
            $request->search,
            $request->only([
                'start_date',
                'end_date',
                'discount_type'
            ])
        );
        return $this->successResponse(
            __('store::messages.Flash Sales retrieved successfully'),
            FlashSaleListResource::collection($flashSales)
        );
    }
    public function show($id)
    {
        // جلب الـ Flash Sale
        $flashSale = $this->flashSaleService->findById($id);
        // إرجاع الـ JSON باستخدام Resource
        return $this->successResponse(
            __('store::messages.Flash Sale retrieved successfully'),
            new FlashSaleResource($flashSale->load('variants.product'))
        );
    }
}
