<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Core\Models\User;
use Modules\Store\Models\Product;

interface ProductRepositoryInterface
{
    public function all(array $filters);
    public function index(array $filters = []);
    public function getByStore($storeId, array $filters = []);
    public function findById(int $id);
    public function findByIdWithDetails(int $id);
    public function create(array $data);
    public function update(array $data, Product $product);
    public function delete(Product $product);
    public function duplicate(int $productId): Product;
    public function createGallery(int $productId, array $galleryFiles);
    public function deleteGallery(int $productId);
    public function createOption(int $productId, string $name, string $type = 'text');
    public function createOptionValue(int $optionId, string $value);
    public function getVariantsByStore(int $storeId, array $filters = []);
    public function getByStoreWithVariants(int $storeId);
    public function getVariantsByProduct($productId);
    public function createVariant(array $data);
    public function updateVariant(int $variantId, array $data);
    public function attachVariantOptionValues(int $variantId, array $optionValueIds);
    public function deleteVariants(int $productId): void;
    public function deleteOptionsAndValues(int $productId): void;
    public function deleteGalleryItem(int $galleryItemId): bool;
    public function updateGallerySort(int $productId, array $gallerySortData): bool;
    public function validateGalleryItems(int $productId, array $galleryIds): bool;
    public function getRelatedProducts(int $productId, ?string $search = null);
    public function getBestSellers(int $limit = 9);
    public function getBestSellersByStore(int $storeId, int $limit = 9);
    public function getDiscountedProductsByStore(int $storeId, int $limit = 9);
    public function getSuggestedProductsByStore(int $storeId, int $limit = 10);
    public function getProductsForCompare(array $productIds);
    public function getSuggestedProducts(int $productId, int $limit = 10);
    public function hydrateProductsForSearch(array $productIds);
    public function findByIds(array $productIds, array $relations = []);
    public function findVariantsByIds(array $productIds);
    public function calculateMinVariantPrice(int $productId): ?float;
    public function autocomplete(string $query, int $limit = 10);

    public function chunkProducts(int $chunkSize, callable $callback);
    public function deleteInTransaction(callable $callback);
}
