<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\Product;
use Modules\Store\Models\ProductPackage;

interface PackageRepositoryInterface
{
    public function index(array $filters);
    /**
     * Get all packages for a product
     */
    public function getByProduct(int $productId, bool $activeOnly = false);

    /**
     * Get all packages for a store
     */
    public function getByStore(int $storeId, bool $activeOnly = false, bool $paginate = true);

    /**
     * Find a package by ID
     */
    public function findById(int $id): ProductPackage;

    /**
     * Create a new package
     */
    public function create(array $data): ProductPackage;

    /**
     * Update a package
     */
    public function update(array $data, ProductPackage $package): ProductPackage;

    /**
     * Delete a package
     */
    public function delete(ProductPackage $package): bool;

    /**
     * Attach items to a package
     */
    public function attachItems(int $packageId, array $items): void;

    /**
     * Detach items from a package
     */
    public function detachItems(int $packageId, array $itemIds): void;

    /**
     * Sync items for a package
     */
    public function syncItems(int $packageId, array $items): void;

    /**
     * Get package with items and related data
     */
    public function findWithItems(int $id): ProductPackage;

    /**
     * Check if product exists
     */
    public function productExists(int $productId): bool;

    /**
     * Check if variant exists
     */
    public function variantExists(int $variantId): bool;

    /**
     * Check if variant belongs to product
     */
    public function variantBelongsToProduct(int $variantId, int $productId): bool;

    /**
     * Get product by ID
     */
    public function getProduct(int $productId): ?Product;

    public function toggleActivation(ProductPackage $package): ProductPackage;

}

