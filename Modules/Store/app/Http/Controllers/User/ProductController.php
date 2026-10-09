<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\BestSellerResource;
use Modules\Store\Http\Resources\User\ProductDetailsResource;
use Modules\Store\Http\Resources\User\ProductListResource;
use Modules\Store\Http\Resources\User\ProductAutocompleteResource;
use Modules\Store\Services\PackageService;
use Modules\Store\Services\ProductService;
use Exception;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected PackageService $packageService
    ) {}
    public function getProducts(Request $request)
    {
        $filters = $request->all();
        // Normalize category_ids: ensure array (support category_ids[]=23 or category_ids=23)
        if (array_key_exists('category_ids', $filters)) {
            $filters['category_ids'] = is_array($filters['category_ids'])
                ? array_values(array_map('intval', $filters['category_ids']))
                : [ (int) $filters['category_ids'] ];
        }
        $products = $this->productService->all($filters);
        return $this->successResponse(__('store::messages.success'), [
            'products' => ProductListResource::collection($products['data']),
            'meta' => $products['meta']
        ]);
    }
    /**
     * Autocomplete search endpoint for ecommerce
     * Returns lightweight product suggestions as user types
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:100',
                'limit' => 'sometimes|integer|min:1|max:20'
            ]);

            $query = $request->input('q');
            $limit = (int) $request->input('limit', 10);

            $products = $this->productService->autocomplete($query, $limit);

            return $this->successResponse(__('store::messages.products.autocomplete_retrieved_successfully'), [
                'products' => ProductAutocompleteResource::collection($products),
                'count' => count($products)
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function show($id)
    {
        try {
            $product = $this->productService->findByIdWithDetails($id);

            if ($product->status->value !== 'active') {
                return $this->errorResponse(__('store::messages.products.not_available'), 404);
            }

            $suggestedProducts = $this->productService->getSuggestedProducts($id, 9);


            $bestSellers = $this->productService
            ->getBestSellersByStore($product->store_id, 9)
            ->where('id', '!=', $product->id);

            return $this->successResponse(__('store::messages.products.retrieved_successfully'), [
                'product' => new ProductDetailsResource($product),
                'suggested_products' => ProductListResource::collection($suggestedProducts),
                'best_sellers' => BestSellerResource::collection($bestSellers),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }
}
