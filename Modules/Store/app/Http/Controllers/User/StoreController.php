<?php

namespace Modules\Store\Http\Controllers\User;

use Modules\Store\Services\StoreService;
use Modules\Store\Services\ProductService;
use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\StoreResource;
use Modules\Store\Http\Resources\User\StoreCategoryResource;
use Modules\Store\Http\Resources\User\ProductListResource;

class StoreController extends Controller
{
    public function __construct(
        protected StoreService $storeService,
        protected ProductService $productService
    ) {}

    public function show($id)
    {
        $store = $this->storeService->findById($id);
        return $this->successResponse('success', [
            'store' => new StoreResource($store),
            'return_policy' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Fugiat blanditiis ipsum voluptatibus, harum eligendi, sequi ducimus pariatur est totam similique quos officia cumque tenetur quia dolorem. Id aliquam quam commodi',
            'shipping_policy' => 'Lorem ipsum dolor sit, amet consectetur adipisicing elit. Fugiat blanditiis ipsum voluptatibus, harum eligendi, sequi ducimus pariatur est totam similique quos officia cumque tenetur quia dolorem. Id aliquam quam commodi',
        ]);
    }

    public function getCategories($storeId)
    {
        $categories = $this->storeService->getCategoriesByStore($storeId);
        return $this->successResponse('Categories retrieved successfully', StoreCategoryResource::collection($categories));
    }

    public function getBestSellers($storeId)
    {
        $products = $this->productService->getBestSellersByStore($storeId);
        return $this->successResponse('Best sellers retrieved successfully', ProductListResource::collection($products));
    }

    public function getDiscountedProducts($storeId)
    {
        $products = $this->productService->getDiscountedProductsByStore($storeId);
        return $this->successResponse('Discounted products retrieved successfully', ProductListResource::collection($products));
    }

    public function getSuggestedProducts($storeId)
    {
        $products = $this->productService->getSuggestedProductsByStore($storeId);
        return $this->successResponse('Suggested products retrieved successfully', ProductListResource::collection($products));
    }
}
