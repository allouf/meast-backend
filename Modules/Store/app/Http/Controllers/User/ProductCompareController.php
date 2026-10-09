<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\ProductDetailsResource;
use Modules\Store\Services\ProductCompareService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\Response;

class ProductCompareController extends Controller
{
    public function __construct(
        protected ProductCompareService $productCompareService
    ) {}
    /**
     * Compare products by IDs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function compare(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:products,id',
            ]);
            $productIds = $request->input('ids', []);
            $products = $this->productCompareService->getProductsForCompare($productIds);
            $groupedProducts = [];
            foreach ($products as $index => $product) {
                $groupedProducts[(string)$index] = (new ProductDetailsResource($product))->toArray($request);
            }
            return $this->successResponse('Products retrieved successfully', (object)$groupedProducts);
        } catch (ValidationException $e) {
            return $this->exceptionResponse($e);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'An error occurred while comparing products.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
