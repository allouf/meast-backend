<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\Admin\FlashSaleListResource;
use Modules\Store\Http\Resources\Admin\FlashSaleResource;
use Modules\Store\Services\FlashSaleService;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function __construct(
        protected FlashSaleService $flashSaleService
    ) {}


    public function index(Request $request)
    {
        $filters = $request->only([
            'store_id',
            'status',
            'date_status',
            'discount_type',
            'search',
            'start_from',
            'start_to',
            'end_from',
            'end_to',
            'has_stock',
            'sort',
            'page',
            'per_page'
        ]);

        $result = $this->flashSaleService->all($filters);

        return $this->successResponse(
            __('store::messages.Flash Sales retrieved successfully'),
            [
                'flash_sales' => FlashSaleListResource::collection($result['data']),
                'meta' => $result['meta']
            ]
        );
    }


    public function destroy($id)
    {
        $this->flashSaleService->delete($id);
        return $this->successResponse(__('store::messages.Flash Sale deleted successfully'));
    }


    public function show($id)
    {
        try {
            $flashSale = $this->flashSaleService->findByIdWithStock($id);

            return $this->successResponse(
                __('store::messages.Flash Sale retrieved successfully'),
                new FlashSaleResource($flashSale)
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }
}
