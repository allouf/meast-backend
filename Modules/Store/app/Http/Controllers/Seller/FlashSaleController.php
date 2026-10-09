<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\FlashSaleListResource;
use Modules\Store\Http\Resources\User\FlashSaleResource;
use Modules\Store\Services\FlashSaleService;
use Illuminate\Http\Request;
use Modules\Store\Http\Requests\Seller\FlashSaleRequest;

class FlashSaleController extends Controller
{
    public function __construct(
        protected FlashSaleService $flashSaleService
    ) {}
    public function getByStore($storeId, Request $request)
    {
          $search = $request->get('search');
        $result = $this->flashSaleService->getFlashSalesByStore($storeId, true, $search);

        $response = [
            'flash_sales' => FlashSaleListResource::collection($result['data'])
        ];

        if ($result['meta']) {
            $response['meta'] = $result['meta'];
        }

        return $this->successResponse(
            __('store::messages.Flash Sales retrieved successfully'),
            $response
        );
    }
    public function store(FlashSaleRequest $request)
    {
        $flashSale = $this->flashSaleService->createFlashSale($request->validated());
        return $this->successResponse(__('store::messages.Flash Sale created successfully'), new FlashSaleResource($flashSale));
    }

    public function update(FlashSaleRequest $request, $id)
    {
        $flashSale = $this->flashSaleService->findById($id);
        $updatedFlashSale = $this->flashSaleService->updateFlashSale($request->validated(), $flashSale);
        return $this->successResponse(__('store::messages.Flash Sale updated successfully'), new FlashSaleResource($updatedFlashSale));
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
