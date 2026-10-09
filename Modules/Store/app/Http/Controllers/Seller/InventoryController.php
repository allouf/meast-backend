<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Imports\UpdateProductVariantsImport;
use Modules\Store\Exports\ProductVariantsExport;
use Modules\Store\Http\Resources\User\InventoryResource;
use Modules\Store\Services\ProductService;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Modules\Store\Http\Requests\Seller\ImportVariantsRequest;
use Illuminate\Http\Request;
class InventoryController extends Controller
{
    public function __construct(protected ProductService $productService) {}

    public function getVariantsByStore(Request $request, $storeId)
    {
        $filters = $request->all();
        $variants = $this->productService->getVariantsByStore($storeId, $filters);
        return $this->successResponse('success', [
            'data' => InventoryResource::collection($variants['data']),
            'meta' => $variants['meta']
        ]);
    }
    public function exportProductVariants()
    {
        return Excel::download(
            new ProductVariantsExport,
            'product_variants_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }
    public function importVariants(ImportVariantsRequest $request)
    {
        try {
            Excel::import(new UpdateProductVariantsImport, $request->file('file'));
            return $this->successResponse('Variants updated successfully!');
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}

