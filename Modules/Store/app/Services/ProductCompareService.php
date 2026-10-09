<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Support\Collection;

class ProductCompareService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Get products for comparison by IDs
     *
     * @param array $productIds
     * @return Collection
     */
    public function getProductsForCompare(array $productIds): Collection
    {
        return $this->productRepository->getProductsForCompare($productIds);
    }
}

