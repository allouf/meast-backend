<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\Admin\CouponListResource;
use Modules\Store\Http\Resources\Admin\CouponResource;
use Modules\Store\Services\CouponService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService,
    ) {}


    public function index(Request $request)
    {
        $filters = $request->validate([
            'store_id'        => 'nullable|integer|exists:stores,id',
            'code'            => 'nullable|string|max:100',
            'discount_type'   => 'nullable|in:percentage,fixed',
            'scope'           => 'nullable|in:all,products,categories',
            'status'          => 'nullable|in:valid,expired',
            'has_usage'       => 'nullable|boolean',
            'created_from'    => 'nullable|date',
            'created_to'      => 'nullable|date',
            'expires_from'    => 'nullable|date',
            'expires_to'      => 'nullable|date',
            'sort'            => 'nullable|in:most_used,expires_soon,latest',
        ]);

        $coupons = $this->couponService->index($filters);

        return $this->successResponse(
            'Coupons retrieved successfully',
            [
                'coupons' => CouponListResource::collection($coupons['data']),
                'meta' => $coupons['meta']
            ]
        );
    }

    public function destroy($id)
    {
        try {
            $coupon = $this->couponService->findById($id);
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
            return $this->successResponse(__('store::messages.Coupon details fetched successfully'), new CouponResource($coupon));
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('store::messages.Coupon not found'), 404);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
