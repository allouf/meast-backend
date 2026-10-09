<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\CouponListResource;
use Modules\Store\Http\Resources\User\CouponResource;
use Modules\Store\Services\CouponService;
use Modules\Store\Services\StoreMemberPermissionService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Modules\Store\Http\Requests\Seller\CouponRequest;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService,
        protected StoreMemberPermissionService $permissionService
    ) {}
    public function indexByStore(Request $request, $storeId)
    {
        try {
            $this->permissionService->canManageCoupons($storeId);

            $coupons = $this->couponService->getByStoreId(
                $storeId,
                $request->get('search'),
                $request->get('status')
            );

            return $this->successResponse(__('store::messages.Coupons fetched successfully'), [
                'coupons' => CouponListResource::collection($coupons['data']),
                'meta' => $coupons['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function store(CouponRequest $request)
    {
        $data = $request->validated();
        $this->permissionService->canManageCoupons($data['store_id']);
        $coupon = $this->couponService->create($data);
        return $this->successResponse(__('store::messages.Coupon created successfully'), new CouponResource($coupon));
    }
    public function destroy($id)
    {
        try {
            $coupon = $this->couponService->findById($id);
            $this->permissionService->canManageCoupons($coupon->store_id);
            $this->couponService->delete($id);
            return $this->successResponse(__('store::messages.Coupon deleted successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }


    public function show($id)
    {
        try {
            $coupon = $this->couponService->findById($id);
            $this->permissionService->canManageCoupons($coupon->store_id);
            return $this->successResponse(__('store::messages.Coupon details fetched successfully'), new CouponResource($coupon));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('store::messages.Coupon not found'), 404);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
