<?php

namespace Modules\Store\Services;

use Modules\Store\Enums\ProductStatus;
use App\Library\Elasticsearch\Elasticsearch;
use Modules\Store\Library\Elasticsearch\ProductElasticsearch;
use Modules\Store\Models\Category;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductVariant;
use Modules\Store\Repositories\Interfaces\ProductRepositoryInterface;
use App\Utilities\FileManager;
use Modules\Store\Services\StoreMemberPermissionService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Exception;

class ProductService
{
    protected $productRepository;
    protected $permissionService;
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreMemberPermissionService $permissionService,
        protected Elasticsearch $elastic
    ) {
        $this->productRepository = $productRepository;
        $this->permissionService = $permissionService;
    }
    public function all(array $filters)
    {
        $filters = $this->expandCategoryIdsToIncludeDescendants($filters);
        $hasSearch = !empty($filters['search']);
        $hasFilters = !empty($filters['category_id']) || !empty($filters['category_ids'])
            || !empty($filters['store_id'])
            || isset($filters['min_price']) || isset($filters['max_price'])
            || isset($filters['rating']) || isset($filters['sort_by']) || isset($filters['sort_order']);
        if ($hasSearch || $hasFilters) {
            return $this->searchWithElasticsearch($filters);
        }
        return $this->productRepository->all($filters);
    }

    /**
     * Expand category_ids to include each category and all its descendants (children).
     * When a parent id is passed, results include products from parent and all child categories.
     */
    private function expandCategoryIdsToIncludeDescendants(array $filters): array
    {
        $ids = $filters['category_ids'] ?? null;
        if (empty($ids) || !is_array($ids)) {
            if (!empty($filters['category_id'])) {
                $ids = [ (int) $filters['category_id'] ];
            } else {
                return $filters;
            }
        }
        $ids = array_map('intval', $ids);
        $expanded = [];
        foreach ($ids as $id) {
            $category = Category::find($id);
            if ($category) {
                $expanded = array_merge(
                    $expanded,
                    $category->descendantsAndSelf($category->id)->pluck('id')->toArray()
                );
            } else {
                $expanded[] = $id;
            }
        }
        $filters['category_ids'] = array_values(array_unique($expanded));
        return $filters;
    }
    /**
     * Search products using Elasticsearch
     */
    private function searchWithElasticsearch(array $filters): array
    {
        $productES = new ProductElasticsearch();
        $result = $productES->searchProductsWithFilters($filters);
        $productIds = collect($result['hits']['hits'])
            ->pluck('_id')
            ->map(fn($id) => (int)$id)
            ->toArray();
        $total = $result['hits']['total']['value'] ?? 0;
        if (empty($productIds)) {
            $page = (int)($filters['page'] ?? 1);
            $perPage = (int)($filters['per_page'] ?? 15);
            return [
                'data' => collect([]),
                'meta' => [
                    'total' => 0,
                    'current_page' => $page,
                    'last_page' => 0,
                    'per_page' => $perPage,
                ]
            ];
        }
        $products = $this->productRepository->hydrateProductsForSearch($productIds);
        $page = (int)($filters['page'] ?? 1);
        $perPage = (int)($filters['per_page'] ?? 15);
        $from = ($page - 1) * $perPage;
        $paginatedProducts = $products->slice($from, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $paginatedProducts,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        return [
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ]
        ];
    }
    /**
     * Autocomplete search for products - optimized for fast, lightweight results
     * Returns minimal product data suitable for autocomplete dropdowns
     */
    public function autocomplete(string $query, int $limit = 10)
    {
        if (empty(trim($query))) {
            return [];
        }
        $must = [];
        $filter = [];
        if ($query) {
            $must[] = [
                'bool' => [
                    'should' => [
                        ['prefix' => ['name.keyword' => strtolower($query)]],
                        ['match_phrase_prefix' => [
                            'name' => [
                                'query' => $query,
                                'boost' => 2
                            ]
                        ]],
                        ['match' => [
                            'name' => [
                                'query' => $query,
                                'fuzziness' => 'AUTO',
                                'operator' => 'and'
                            ]
                        ]]
                    ],
                    'minimum_should_match' => 1
                ]
            ];
        }
        $filter[] = ['term' => ['status' => 'active']];
        $elasticQuery = $this->elastic->buildQuery(
            must: $must,
            filter: $filter
        );
        $elasticQuery['size'] = $limit;
        $elasticQuery['_source'] = ['id', 'name', 'slug', 'price', 'status'];
        try {
            $result = $this->elastic->search('products', $elasticQuery);
            $productIds = collect($result['hits']['hits'])->pluck('_id')->map(fn($id) => (int)$id)->toArray();
            if (empty($productIds)) {
                return [];
            }
            $products = $this->productRepository->findByIds($productIds, ['gallery', 'category']);
            $orderedProducts = collect($productIds)->map(function ($id) use ($products) {
                return $products->firstWhere('id', $id);
            })->filter()->values();
            return $orderedProducts;
        } catch (Exception $e) {
            return $this->productRepository->autocomplete($query, $limit);
        }
    }
    public function search(array $filters): array
    {
        $keyword = Arr::get($filters, 'keyword');
        $category = Arr::get($filters, 'category_id');
        $priceFrom = Arr::get($filters, 'price_from');
        $priceTo = Arr::get($filters, 'price_to');
        $page = (int) Arr::get($filters, 'page', 1);
        $perPage = (int) Arr::get($filters, 'per_page', 15);
        $must = [];
        $filter = [];
        if ($keyword) {
            $must[] = ['match' => ['name' => $keyword]];
        }
        if ($category) {
            $filter[] = ['term' => ['category_id' => (int)$category]];
        }
        if ($priceFrom || $priceTo) {
            $filter[] = [
                'range' => [
                    'price' => [
                        'gte' => $priceFrom ?? 0,
                        'lte' => $priceTo ?? 999999,
                    ]
                ]
            ];
        }
        $query = $this->elastic->buildQuery(
            must: $must,
            filter: $filter
        );
        $from = ($page - 1) * $perPage;
        $query['from'] = $from;
        $query['size'] = $perPage;
        $result = $this->elastic->search('products', $query);
        $hits = collect($result['hits']['hits'])->map(fn($item) => (object) $item['_source']);
        $paginator = new LengthAwarePaginator(
            $hits,
            $result['hits']['total']['value'] ?? 0,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        return [
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ]
        ];
    }
    public function index(array $filters = [])
    {
        return $this->productRepository->index($filters);
    }
    
    public function getByStore($storeId, array $filters = [])
    {
        return $this->productRepository->getByStore($storeId, $filters);
    }
    public function findById(int $id)
    {
        $product = $this->productRepository->findById($id);
        return $product;
    }
    public function findByIdWithDetails(int $id)
    {
        return $this->productRepository->findByIdWithDetails($id);
    }
    public function getRelatedProducts(int $productId, ?string $search = null)
    {
        return $this->productRepository->getRelatedProducts($productId, $search);
    }
    public function getBestSellers(int $limit = 9)
    {
        return $this->productRepository->getBestSellers($limit);
    }
    public function getBestSellersByStore(int $storeId, int $limit = 9)
    {
        return $this->productRepository->getBestSellersByStore($storeId, $limit);
    }
    public function getDiscountedProductsByStore(int $storeId, int $limit = 9)
    {
        return $this->productRepository->getDiscountedProductsByStore($storeId, $limit);
    }
    public function getSuggestedProductsByStore(int $storeId, int $limit = 10)
    {
        return $this->productRepository->getSuggestedProductsByStore($storeId, $limit);
    }
    public function getSuggestedProducts(int $productId, int $limit = 10)
    {
        return $this->productRepository->getSuggestedProducts($productId, $limit);
    }
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $galleryFiles = $data['gallery'] ?? [];
            unset($data['gallery']);
            $optionsData = $this->parseOptionsFromFormData($data);
            unset($data['options']);
            $variantsData = $this->parseVariantsFromFormData($data);
            unset($data['variants']);
            $data['is_active'] = $data['is_active'] ?? true;
            $data['number_of_buys'] = $data['number_of_buys'] ?? 0;
            $data['avg_rating'] = $data['avg_rating'] ?? 0;

            // Store discount info before removing price
            $discountValue = $data['discount_value'] ?? null;
            $discountType = $data['discount_type'] ?? null;

            // Remove price from main product - it will be calculated from variants
            unset($data['price']);
            unset($data['price_before_discount']);

            // Create product without price
            $product = $this->productRepository->create($data);

            if (!empty($galleryFiles)) {
                $this->createGallery($product->id, $galleryFiles);
            }

            $valueMap = $this->createOptionsAndValues($product, $optionsData);
            $this->createVariants($product, $variantsData, $valueMap);

            // Calculate and set product price from minimum variant price
            $minVariantPrice = $this->productRepository->calculateMinVariantPrice($product->id);
            if ($minVariantPrice !== null) {
                $updateData = ['price' => $minVariantPrice];

                // Apply discount if exists
                if (!empty($discountValue) && !empty($discountType)) {
                    $updateData['price_before_discount'] = $minVariantPrice;
                    if ($discountType === 'percentage') {
                        $updateData['price'] = $minVariantPrice - ($minVariantPrice * ($discountValue / 100));
                    } elseif ($discountType === 'fixed') {
                        $updateData['price'] = $minVariantPrice - $discountValue;
                    }
                    if ($updateData['price'] < 0) {
                        $updateData['price'] = 0;
                    }
                }

                $product->update($updateData);
                $product->refresh();
            }

            return $product;
        });
    }
    public function update(array $data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $product = $this->findById($id);
            if (!$product) {
                throw new NotFoundHttpException('Product not found.');
            }

            $galleryFiles = $data['gallery'] ?? [];
            unset($data['gallery']);
            $optionsData = $this->parseOptionsFromFormData($data);
            unset($data['options']);
            $variantsData = $this->parseVariantsFromFormData($data);
            unset($data['variants']);

            // Remove price from main product - it will be calculated from variants
            unset($data['price']);
            unset($data['price_before_discount']);

            if (!isset($data['is_active'])) {
                $data['is_active'] = $product->is_active;
            }
            if (!isset($data['number_of_buys'])) {
                $data['number_of_buys'] = $product->number_of_buys;
            }
            if (!isset($data['avg_rating'])) {
                $data['avg_rating'] = $product->avg_rating;
            }
            if (!isset($data['discount_value'])) {
                $data['discount_value'] = $product->discount_value;
            }
            if (!isset($data['discount_type'])) {
                $data['discount_type'] = $product->discount_type;
            }

            // Store discount info for price calculation
            $discountValue = $data['discount_value'] ?? null;
            $discountType = $data['discount_type'] ?? null;

            $updatedProduct = $this->productRepository->update($data, $product);

            if (!empty($galleryFiles)) {
                if ($product->gallery && $product->gallery->isNotEmpty()) {
                    foreach ($product->gallery as $galleryItem) {
                        FileManager::delete($galleryItem->file_path);
                    }
                }
                $this->productRepository->deleteGallery($product->id);
                $this->createGallery($product->id, $galleryFiles);
            }

            if (!empty($optionsData) || !empty($variantsData)) {
                $this->productRepository->deleteOptionsAndValues($product->id);
                $valueMap = $this->createOptionsAndValues($product, $optionsData);
                $this->productRepository->deleteVariants($product->id);
                $this->createVariants($product, $variantsData, $valueMap);
            }

            // Calculate and set product price from minimum variant price
            $minVariantPrice = $this->productRepository->calculateMinVariantPrice($product->id);
            if ($minVariantPrice !== null) {
                $updatePriceData = ['price' => $minVariantPrice];

                // Apply discount if exists
                if (!empty($discountValue) && !empty($discountType)) {
                    $updatePriceData['price_before_discount'] = $minVariantPrice;
                    if ($discountType === 'percentage') {
                        $updatePriceData['price'] = $minVariantPrice - ($minVariantPrice * ($discountValue / 100));
                    } elseif ($discountType === 'fixed') {
                        $updatePriceData['price'] = $minVariantPrice - $discountValue;
                    }
                    if ($updatePriceData['price'] < 0) {
                        $updatePriceData['price'] = 0;
                    }
                }

                $updatedProduct->update($updatePriceData);
                $updatedProduct->refresh();
            }

            return $updatedProduct;
        });
    }
    public function delete($id)
    {
        $product = $this->findById($id);
        if ($product) {
            if ($product->gallery) {
                foreach ($product->gallery as $galleryItem) {
                    FileManager::delete($galleryItem->file_path);
                }
            }
            if ($product->variants) {
                foreach ($product->variants as $variant) {
                    if ($variant->image) {
                        FileManager::delete($variant->image);
                    }
                }
            }
        }
        return $this->productRepository->delete($product);
    }
    private function createGallery(int $productId, array $galleryFiles): void
    {
        $this->productRepository->createGallery($productId, $galleryFiles);
    }
    private function createOptionsAndValues($product, array $optionsData): array
    {
        $valueMap = [];
        foreach ($optionsData as $option) {
            $type = $option['type'] ?? 'text';
            $opt = $this->productRepository->createOption($product->id, $option['name'], $type);
            foreach ($option['values'] as $val) {
                $value = $this->productRepository->createOptionValue($opt->id, $val);
                $valueMap[$option['name']][$val] = $value->id;
            }
        }
        return $valueMap;
    }
    public function getVariantsByStore(int $storeId, array $filters = [])
    {
        $filters['store_id'] = $storeId;
        
        if (!empty($filters['search'])) {
            $productES = new ProductElasticsearch();
            $result = $productES->searchProductsWithFilters($filters);
            
            $productIds = collect($result['hits']['hits'])
                ->pluck('_id')
                ->map(fn($id) => (int)$id)
                ->toArray();
                
            $total = $result['hits']['total']['value'] ?? 0;
            
            if (empty($productIds)) {
                $page = (int)($filters['page'] ?? 1);
                $perPage = (int)($filters['per_page'] ?? 15);
                return [
                    'data' => collect([]),
                    'meta' => [
                        'total' => 0,
                        'current_page' => $page,
                        'last_page' => 0,
                        'per_page' => $perPage,
                    ]
                ];
            }
            // Fetch products with variants from repository
            $products = $this->productRepository->findVariantsByIds($productIds);
            
            $page = (int)($filters['page'] ?? 1);
            $perPage = (int)($filters['per_page'] ?? 15);
            
            $paginator = new LengthAwarePaginator(
                $products,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            return [
                'data' => $paginator->items(),
                'meta' => [
                    'total' => $paginator->total(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                ]
            ];
        }
        
        return $this->productRepository->getVariantsByStore($storeId, $filters);
    }
    public function getByStoreWithVariants(int $storeId)
    {
        return $this->productRepository->getByStoreWithVariants($storeId);
    }
    public function getVariantsByProduct($productId)
    {
        return $this->productRepository->getVariantsByProduct($productId);
    }
    public function updateVariant(int $variantId, array $data)
    {
        $variant = $this->productRepository->updateVariant($variantId, $data);

        // Recalculate product price from minimum variant price
        $product = $this->productRepository->findById($variant->product_id);
        if ($product) {
            $minVariantPrice = $this->productRepository->calculateMinVariantPrice($product->id);
            if ($minVariantPrice !== null) {
                $updatePriceData = ['price' => $minVariantPrice];

                // Apply discount if exists
                if (!empty($product->discount_value) && !empty($product->discount_type)) {
                    $updatePriceData['price_before_discount'] = $minVariantPrice;
                    if ($product->discount_type === 'percentage') {
                        $updatePriceData['price'] = $minVariantPrice - ($minVariantPrice * ($product->discount_value / 100));
                    } elseif ($product->discount_type === 'fixed') {
                        $updatePriceData['price'] = $minVariantPrice - $product->discount_value;
                    }
                    if ($updatePriceData['price'] < 0) {
                        $updatePriceData['price'] = 0;
                    }
                }

                $product->update($updatePriceData);
            }
        }

        return $variant;
    }
    private function createVariants($product, array $variantsData, array $valueMap): void
    {
        foreach ($variantsData as $variant) {
            // Require variant price - no fallback to product price
            if (!isset($variant['price']) || $variant['price'] === null) {
                throw new HttpException(400, 'Variant price is required when creating variants.');
            }

            $variantPrice = $variant['price'];

            if (!$this->permissionService->canModifyProductPrices($product->store_id)) {
                throw new HttpException(403, 'You do not have permission to modify product prices.');
            }

            $variantData = [
                'product_id' => $product->id,
                'sku' => $variant['sku'] ?? null,
                'price' => $variantPrice,
                'low_stock_threshold' => $variant['low_stock_threshold'] ?? 0,
                'stock' => $variant['stock'] ?? 0,
                'is_active' => $variant['is_active'] ?? true,
            ];
            if (isset($variant['image']) && $variant['image']) {
                $variantData['image'] = FileManager::upload('product-variants', $variant['image']);
            }
            $variantModel = $this->productRepository->createVariant($variantData);
            if (!empty($variant['option_values'])) {
                $optionValueIds = [];
                if ($this->is_assoc($variant['option_values'])) {
                    foreach ($variant['option_values'] as $optName => $optVal) {
                        if (isset($valueMap[$optName][$optVal])) {
                            $optionValueIds[] = $valueMap[$optName][$optVal];
                        }
                    }
                } else {
                    $optionNames = array_keys($valueMap);
                    foreach ($variant['option_values'] as $index => $optVal) {
                        if (isset($optionNames[$index]) && isset($valueMap[$optionNames[$index]][$optVal])) {
                            $optionValueIds[] = $valueMap[$optionNames[$index]][$optVal];
                        }
                    }
                }
                $this->productRepository->attachVariantOptionValues($variantModel->id, $optionValueIds);
            }
        }
    }
    public function deleteOptionsAndValues(int $productId)
    {
        return $this->productRepository->deleteOptionsAndValues($productId);
    }
    private function parseOptionsFromFormData(array $data): array
    {
        $optionsData = [];
        if (isset($data['options']) && is_array($data['options'])) {
            return $data['options'];
        }
        $optionIndex = 0;
        while (isset($data["options[{$optionIndex}][name]"])) {
            $optionName = $data["options[{$optionIndex}][name]"];
            $values = [];
            $valueIndex = 0;
            while (isset($data["options[{$optionIndex}][values][{$valueIndex}]"])) {
                $values[] = $data["options[{$optionIndex}][values][{$valueIndex}]"];
                $valueIndex++;
            }
            if (!empty($optionName) && !empty($values)) {
                $optionsData[] = [
                    'name' => $optionName,
                    'values' => $values
                ];
            }
            $optionIndex++;
        }
        return $optionsData;
    }
    private function parseVariantsFromFormData(array $data): array
    {
        $variantsData = [];
        if (isset($data['variants']) && is_array($data['variants'])) {
            return $data['variants'];
        }
        $variantIndex = 0;
        while (isset($data["variants[{$variantIndex}][sku]"])) {
            $variant = [
                'sku' => $data["variants[{$variantIndex}][sku]"] ?? null,
                'price' => $data["variants[{$variantIndex}][price]"] ?? null,
                'stock' => $data["variants[{$variantIndex}][stock]"] ?? 0,
                'low_stock_threshold' => $data["variants[{$variantIndex}][low_stock_threshold]"] ?? 0,
                'image' => $data["variants[{$variantIndex}][image]"] ?? null,
                'is_active' => $data["variants[{$variantIndex}][is_active]"] ?? true,
                'option_values' => []
            ];
            $optionValueIndex = 0;
            while (isset($data["variants[{$variantIndex}][option_values][{$optionValueIndex}]"])) {
                $variant['option_values'][] = trim($data["variants[{$variantIndex}][option_values][{$optionValueIndex}]"]);
                $optionValueIndex++;
            }
            $variantsData[] = $variant;
            $variantIndex++;
        }
        return $variantsData;
    }
    private function is_assoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
    public function switchActivation($id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            throw new NotFoundHttpException('Product not found.');
        }
        $newStatus = $product->status === ProductStatus::ACTIVE
            ? ProductStatus::INACTIVE
            : ProductStatus::ACTIVE;
        $product->update(['status' => $newStatus->value]);
        return $product->fresh();
    }
    public function duplicate($id)
    {
        $product = $this->findById($id);
        if (!$product) {
            throw new NotFoundHttpException('Product not found.');
        }
        return DB::transaction(function () use ($id) {
            return $this->productRepository->duplicate($id);
        });
    }
    public function deleteGalleryItem(int $galleryItemId)
    {
        return $this->productRepository->deleteGalleryItem($galleryItemId);
    }
    public function updateGallerySort(int $productId, array $gallerySortData)
    {
        return $this->productRepository->updateGallerySort($productId, $gallerySortData);
    }
    public function validateGalleryItems(int $productId, array $galleryIds)
    {
        return $this->productRepository->validateGalleryItems($productId, $galleryIds);
    }
    protected function deleteProductRelations(Product $product): void
    {
        // عرض العلاقات قبل الحذف
        dd([
            'gallery_ids' => $product->gallery()->pluck('id')->toArray(),
            'review_ids' => $product->reviews()->pluck('id')->toArray(),
            'variant_ids' => $product->variants()->pluck('id')->toArray(),
            'option_ids' => $product->options()->pluck('id')->toArray(),
            'package_ids' => $product->packages()->pluck('id')->toArray(),
        ]);

        $product->gallery()->delete();

        $product->reviews()->each(function ($review) {
            $review->images()->delete();
            $review->delete();
        });

        $product->variants()->each(function ($variant) {
            $variant->optionValues()->detach();
            $variant->delete();
        });

        $product->options()->each(function ($option) {
            $option->values()->delete();
            $option->delete();
        });

        $product->packages()->each(function ($package) {
            $package->items()->delete();
            $package->delete();
        });
    }
}
