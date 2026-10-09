<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use App\Utilities\FileManager;
use Modules\Store\Models\CartItem;
use Modules\Store\Models\Category;
use Modules\Store\Models\OrderItem;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductGallery;
use Modules\Store\Models\ProductVariant;
use Modules\Store\Models\ProductVariantOption;
use Modules\Store\Models\ProductVariantOptionValue;
use Modules\Store\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    use PaginateTrait;
    public function __construct(
        protected Product $model,
        protected ProductVariant $variantModel,
        protected ProductVariantOption $optionModel,
        protected ProductVariantOptionValue $optionValueModel,
        protected ProductGallery $galleryModel
    ) {}
    public function all(array $filters)
    {
        $query = $this->model->with(['store', 'category', 'gallery'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock');

        // Handle sorting
        if (!empty($filters['sort_by'])) {
            $sortBy = $filters['sort_by'];
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $allowedSortFields = ['price', 'created_at', 'name', 'avg_rating'];

            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest(); // Default to latest if invalid sort_by
            }
        } else {
            $query->latest(); // Default sorting
        }

        if (!empty($filters['category_id'])) {
            $category = Category::find($filters['category_id']);

            if ($category) {
                $categoryIds = $category
                    ->descendantsAndSelf($category->id)
                    ->pluck('id');

                $query->whereIn('category_id', $categoryIds);
            }
        }

        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $categoryIds = $this->expandCategoryIdsWithDescendants(array_map('intval', $filters['category_ids']));
            $query->whereIn('category_id', $categoryIds);
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (isset($filters['rating'])) {
            $rating = (float) $filters['rating'];
            $query->where('avg_rating', '>=', $rating)
                ->where('avg_rating', '<', $rating + 1);
        }
        return $this->paginate($query);
    }

    /**
     * Expand category IDs to include each category and all its descendants (parent + children).
     */
    private function expandCategoryIdsWithDescendants(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
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
        return array_values(array_unique($expanded));
    }

    public function index(array $filters = [])
    {
        $query = $this->model
            ->with(['store', 'category', 'reviews'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock');

        /* ✅ Filter by store_id (اختياري) */
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        /* 🔍 Search */
        if (!empty($filters['search'])) {
            $searchTerm = trim($filters['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }

        /* 🗂 Categories */
        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        }

        /* 📌 Status */
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        /* 💰 Price range */
        if (!is_null($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!is_null($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        /* 🔖 Discount */
        if (!is_null($filters['has_discount'])) {
            if ($filters['has_discount']) {
                $query->whereNotNull('discount_value')
                      ->where('discount_value', '>', 0);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('discount_value')
                      ->orWhere('discount_value', 0);
                });
            }
        }

        $query->latest();

        return $this->paginate($query);
    }

    public function getByStore($storeId, array $filters = [])
    {
        $query = $this->model->with(['store', 'category', 'reviews'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->where('store_id', $storeId);

        // Filter by search term
        if (!empty($filters['search'])) {
            $searchTerm = trim($filters['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by category_ids
        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Price range filter (based on base price)

        if (!is_null($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!is_null($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Discount filter
        if (!is_null($filters['has_discount'])) {
            if ($filters['has_discount']) {
                $query->whereNotNull('discount_value')
                    ->where('discount_value', '>', 0);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('discount_value')
                        ->orWhere('discount_value', 0);
                });
            }
        }

        $query->latest();
        return $this->paginate($query);
    }
    public function findById(int $id)
    {
        return $this->model->with([
            'store',
            'category',
            'gallery',
            'variants.optionValues',
            'variants.product.options.values',
            'options.values',
            'reviews.user'
        ])->findOrFail($id);
    }
    public function findByIdWithDetails(int $id)
    {
        return $this->model->with([
            'store',
            'category',
            'gallery',
            'variants.optionValues',
            'variants.product.options.values',
            'options.values',
            'reviews.user',
            'reviews.images',
            'packages.items.product',
            'packages.items.variant'
        ])->findOrFail($id);
    }
    // Repository
    public function getRelatedProducts(int $productId, ?string $search = null)
    {
        $product = $this->model->findOrFail($productId);

        $query = $this->model->with(['store', 'category', 'gallery'])
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->where('category_id', $product->category_id)
            ->where('store_id', $product->store_id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }


    public function getBestSellers(int $limit = 9)
    {
        return $this->model->with(['store', 'category'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->where('status', 'active')
            ->where('number_of_buys', '>', 0)
            ->orderBy('number_of_buys', 'desc')
            ->orderBy('avg_rating', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getBestSellersByStore(int $storeId, int $limit = 9)
    {
        return $this->model->with(['store', 'category', 'gallery'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where('number_of_buys', '>', 0)
            ->orderBy('number_of_buys', 'desc')
            ->orderBy('avg_rating', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getDiscountedProductsByStore(int $storeId, int $limit = 9)
    {
        return $this->model->with(['store', 'category', 'gallery'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->whereNotNull('discount_value')
            ->where('discount_value', '>', 0)
            ->orderBy('discount_value', 'desc')
            ->orderBy('number_of_buys', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSuggestedProductsByStore(int $storeId, int $limit = 10)
    {
        return $this->model->with(['store', 'category', 'gallery'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where('avg_rating', '>=', 4)
            ->orderBy('avg_rating', 'desc')
            ->orderBy('number_of_buys', 'desc')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Product
    {
        $product = $this->model->create($data);
        return $product->fresh();
    }
    public function update(array $data, Product $product): Product
    {
        $product->update($data);
        return $product;
    }
    public function delete(Product $product): bool
    {
        return $product->delete();
    }
    public function duplicate(int $productId): Product
    {
        $product = $this->findById($productId);
        $newProductData = $product->replicate()->toArray();
        $newProductData['name'] = $product->name . ' (Copy)';
        $newProductData['slug'] = null;
        $newProductData['number_of_buys'] = 0;
        $newProductData['avg_rating'] = 0;
        $newProduct = $this->model->create($newProductData);
        if ($product->gallery) {
            $galleryData = [];
            $sortOrder = 0;
            foreach ($product->gallery as $galleryItem) {
                $filePath = $galleryItem->file_path;
                $relativePath = str_replace('storage/', '', $filePath);
                if (FileManager::fileExists($filePath)) {
                    $fileName = basename($relativePath);
                    $newFileName = time() . '_' . $sortOrder . '_' . $fileName;
                    $destinationPath = 'products/' . $newFileName;
                    if (FileManager::copyFile($relativePath, $destinationPath)) {
                        $filePath = 'storage/' . $destinationPath;
                    }
                }
                $galleryData[] = [
                    'product_id' => $newProduct->id,
                    'file_path' => $filePath,
                    'file_type' => $galleryItem->file_type,
                    'sort_order' => $sortOrder++,
                    'is_active' => $galleryItem->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($galleryData)) {
                ProductGallery::insert($galleryData);
            }
        }
        $valueMap = [];
        if ($product->options) {
            foreach ($product->options as $option) {
                $newOption = $this->createOption($newProduct->id, $option->name, $option->type ?? 'text');
                foreach ($option->values as $value) {
                    $newValue = $this->createOptionValue($newOption->id, $value->value);
                    $valueMap[$option->name][$value->value] = $newValue->id;
                }
            }
        }
        if ($product->variants) {
            foreach ($product->variants as $variant) {
                $variantData = $variant->toArray();
                unset($variantData['id']);
                $variantData['product_id'] = $newProduct->id;
                $variantData['sku'] = null;
                if ($variant->image) {
                    $imagePath = $variant->image;
                    $relativePath = str_replace('storage/', '', $imagePath);
                    if (FileManager::fileExists($imagePath)) {
                        $fileName = basename($relativePath);
                        $newFileName = time() . '_' . $fileName;
                        $destinationPath = 'product-variants/' . $newFileName;
                        if (FileManager::copyFile($relativePath, $destinationPath)) {
                            $variantData['image'] = 'storage/' . $destinationPath;
                        } else {
                            $variantData['image'] = $imagePath;
                        }
                    } else {
                        $variantData['image'] = $imagePath;
                    }
                }
                $newVariant = $this->createVariant($variantData);
                $optionValueIds = [];
                foreach ($variant->optionValues as $ov) {
                    if (isset($valueMap[$ov->option->name][$ov->value])) {
                        $optionValueIds[] = $valueMap[$ov->option->name][$ov->value];
                    }
                }
                $this->attachVariantOptionValues($newVariant->id, $optionValueIds);
            }
        }
        return $newProduct->fresh();
    }
    public function getVariantsByStore(int $storeId, array $filters = [])
    {
        $query = $this->model->where('store_id', $storeId)
            ->with([
                'variants' => function ($q) {
                    $q->select('id', 'product_id', 'sku', 'stock', 'low_stock_threshold', 'image', 'updated_at')
                        ->with(['optionValues.option']);
                },
                'gallery' => function ($q) {
                    $q->orderBy('sort_order', 'asc');
                }
            ]);

        if (!empty($filters['search'])) {
            $searchTerm = trim($filters['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $this->paginate($query);
    }

    public function getVariantsByProduct($productId)
    {
        return  ProductVariant::where('product_id', $productId)->get();
    }
    public function getByStoreWithVariants(int $storeId)
    {
        $query = $this->model->with([
            'variants.optionValues',
            'variants.product.options.values'
        ])
            ->where('store_id', $storeId)
            ->latest();
        return $this->paginate($query);
    }
    public function createGallery(int $productId, array $galleryFiles)
    {
        $galleryData = [];
        $sortOrder = 0;
        foreach ($galleryFiles as $file) {
            if ($file) {
                $filePath = FileManager::upload('products', $file);
                $fileType = FileManager::getFileTypeFromPath($filePath);
                $galleryData[] = [
                    'product_id' => $productId,
                    'file_path' => $filePath,
                    'file_type' => $fileType,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        if (!empty($galleryData)) {
            ProductGallery::insert($galleryData);
        }
    }
    public function deleteGallery(int $productId): void
    {
        $this->galleryModel->where('product_id', $productId)->delete();
    }
    public function deleteGalleryItem(int $galleryItemId): bool
    {
        $galleryItem = $this->galleryModel->find($galleryItemId);
        if ($galleryItem) {
            FileManager::delete($galleryItem->file_path);
            return $galleryItem->delete();
        }
        return false;
    }
    public function updateGallerySort(int $productId, array $gallerySortData): bool
    {
        try {
            $galleryIds = array_column($gallerySortData, 'id');
            if (!$this->validateGalleryItems($productId, $galleryIds)) {
                return false;
            }
            foreach ($gallerySortData as $item) {
                if (isset($item['id']) && isset($item['sort_order'])) {
                    $this->galleryModel->where('id', $item['id'])
                        ->where('product_id', $productId)
                        ->update(['sort_order' => $item['sort_order']]);
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function validateGalleryItems(int $productId, array $galleryIds): bool
    {
        if (empty($galleryIds)) {
            return false;
        }
        $existingGalleryIds = $this->galleryModel->where('product_id', $productId)
            ->pluck('id')
            ->toArray();
        foreach ($galleryIds as $galleryId) {
            if (!in_array($galleryId, $existingGalleryIds)) {
                return false;
            }
        }
        return true;
    }
    public function createOption(int $productId, string $name, string $type = 'text')
    {
        return $this->optionModel->create([
            'product_id' => $productId,
            'name' => $name,
            'type' => $type
        ]);
    }
    public function calculateMinVariantPrice(int $productId): ?float
    {
        return $this->variantModel->where('product_id', $productId)
            ->where('is_active', true)
            ->min('price');
    }
    public function createOptionValue(int $optionId, string $value)
    {
        return $this->optionValueModel->create([
            'option_id' => $optionId,
            'value' => $value
        ]);
    }
    public function deleteOptionsAndValues(int $productId): void
    {
        $options = $this->optionModel->where('product_id', $productId)->get();
        foreach ($options as $option) {
            $this->optionValueModel->where('option_id', $option->id)->delete();
        }
        $this->optionModel->where('product_id', $productId)->delete();
    }
    public function createVariant(array $data)
    {
        return $this->variantModel->create($data);
    }
    public function updateVariant(int $variantId, array $data)
    {
        $variant = $this->variantModel->findOrFail($variantId);
        if (isset($data['image']) && $data['image']) {
            if ($variant->image) {
                FileManager::delete($variant->image);
            }
            $data['image'] = FileManager::upload('product-variants', $data['image']);
        }
        $variant->update($data);
        return $variant->fresh();
    }
    public function attachVariantOptionValues(int $variantId, array $optionValueIds)
    {
        $variant = $this->variantModel->find($variantId);
        if ($variant) {
            $variant->optionValues()->sync($optionValueIds);
            $observer = app(\Modules\Store\Observers\ProductVariantObserver::class);
            $observer->generateSkuAfterOptionValuesAttached($variant->fresh());
        }
    }
    public function deleteVariants(int $productId): void
    {
        $variants = $this->variantModel->where('product_id', $productId)->get();
        foreach ($variants as $variant) {
            if ($variant->image) {
                FileManager::delete($variant->image);
            }
            $variant->optionValues()->detach();
        }
        $this->variantModel->where('product_id', $productId)->delete();
    }
    public function getProductsForCompare(array $productIds)
    {
        $products = $this->model->with([
            'variants',
            'options',
            'packages',
            'gallery',
            'store',
            'category'
        ])
            ->whereIn('id', $productIds)
            ->get();
        return $products->sortBy(function ($product) use ($productIds) {
            return array_search($product->id, $productIds);
        })->values();
    }
    public function getSuggestedProducts(int $productId, int $limit = 10)
    {
        $product = $this->model->findOrFail($productId);
        $suggestedProductIds = collect();
        $categoryProducts = $this->model->with(['store', 'category'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->where('id', '!=', $productId)
            ->where('status', 'active')
            ->where('category_id', $product->category_id)
            ->limit($limit)
            ->pluck('id');
        $suggestedProductIds = $suggestedProductIds->merge($categoryProducts);
        if ($suggestedProductIds->count() < $limit) {
            $boughtTogetherIds = $this->getProductsBoughtTogether($productId, $limit - $suggestedProductIds->count());
            $viewedTogetherIds = $this->getProductsViewedTogether($productId, $limit - $suggestedProductIds->count());
            $suggestedProductIds = $suggestedProductIds->merge($boughtTogetherIds)->merge($viewedTogetherIds)->unique();
        }
        if ($suggestedProductIds->count() < $limit) {
            $sameStoreIds = $this->model->with(['store', 'category'])
                ->withSum(['variants as total_stock' => function ($q) {
                    $q->where('is_active', true);
                }], 'stock')
                ->where('id', '!=', $productId)
                ->where('status', 'active')
                ->where('store_id', $product->store_id)
                ->whereNotIn('id', $suggestedProductIds)
                ->limit($limit - $suggestedProductIds->count())
                ->pluck('id');
            $suggestedProductIds = $suggestedProductIds->merge($sameStoreIds)->unique();
        }
        if ($suggestedProductIds->count() < $limit) {
            $popularIds = $this->model->with(['store', 'category'])
                ->withSum(['variants as total_stock' => function ($q) {
                    $q->where('is_active', true);
                }], 'stock')
                ->where('id', '!=', $productId)
                ->where('status', 'active')
                ->whereNotIn('id', $suggestedProductIds)
                ->where('number_of_buys', '>', 0)
                ->orderBy('number_of_buys', 'desc')
                ->orderBy('avg_rating', 'desc')
                ->limit($limit - $suggestedProductIds->count())
                ->pluck('id');
            $suggestedProductIds = $suggestedProductIds->merge($popularIds)->unique();
        }
        if ($suggestedProductIds->isEmpty()) {
            return collect();
        }
        return $this->model->with(['store', 'category','gallery'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->whereIn('id', $suggestedProductIds->take($limit)->toArray())
            ->get()
            ->sortBy(function ($item) use ($suggestedProductIds) {
                return array_search($item->id, $suggestedProductIds->toArray());
            })
            ->values();
    }
    /**
     * Get products that were bought together with the given product
     */
    private function getProductsBoughtTogether(int $productId, int $limit): array
    {
        $productVariants = $this->variantModel->where('product_id', $productId)->pluck('id');
        if ($productVariants->isEmpty()) {
            return [];
        }
        $storeOrderIds = OrderItem::whereIn('product_variant_id', $productVariants)
            ->pluck('store_order_id')
            ->unique();
        if ($storeOrderIds->isEmpty()) {
            return [];
        }
        $otherVariantIds = OrderItem::whereIn('store_order_id', $storeOrderIds)
            ->whereNotIn('product_variant_id', $productVariants)
            ->pluck('product_variant_id')
            ->unique();
        if ($otherVariantIds->isEmpty()) {
            return [];
        }
        $boughtTogetherProductIds = $this->variantModel->whereIn('id', $otherVariantIds)
            ->where('product_id', '!=', $productId)
            ->pluck('product_id')
            ->unique()
            ->take($limit)
            ->toArray();
        return $boughtTogetherProductIds;
    }
    /**
     * Get products that were viewed together (in the same cart) with the given product
     */
    private function getProductsViewedTogether(int $productId, int $limit): array
    {
        $productVariants = $this->variantModel->where('product_id', $productId)->pluck('id');
        $cartIds = CartItem::where(function ($query) use ($productId, $productVariants) {
            $query->where('product_id', $productId);
            if ($productVariants->isNotEmpty()) {
                $query->orWhereIn('product_variant_id', $productVariants);
            }
        })
            ->pluck('cart_id')
            ->unique();
        if ($cartIds->isEmpty()) {
            return [];
        }
        $otherProductIds = collect();
        $directProductIds = CartItem::whereIn('cart_id', $cartIds)
            ->where('product_id', '!=', $productId)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique();
        $otherProductIds = $otherProductIds->merge($directProductIds);
        if ($productVariants->isNotEmpty()) {
            $otherVariantIds = CartItem::whereIn('cart_id', $cartIds)
                ->whereNotNull('product_variant_id')
                ->whereNotIn('product_variant_id', $productVariants)
                ->pluck('product_variant_id')
                ->unique();
            if ($otherVariantIds->isNotEmpty()) {
                $variantProductIds = $this->variantModel->whereIn('id', $otherVariantIds)
                    ->where('product_id', '!=', $productId)
                    ->pluck('product_id')
                    ->unique();
                $otherProductIds = $otherProductIds->merge($variantProductIds);
            }
        }
        return $otherProductIds->unique()->take($limit)->toArray();
    }
    /**
     * Find products by IDs with optional relations
     * Maintains the order of IDs provided
     */
    public function findByIds(array $productIds, array $relations = [])
    {
        if (empty($productIds)) {
            return collect([]);
        }
        $query = $this->model->whereIn('id', $productIds);
        if (!empty($relations)) {
            $query->with($relations);
        }
        $products = $query->get();
        return collect($productIds)->map(function ($id) use ($products) {
            return $products->firstWhere('id', $id);
        })->filter()->values();
    }
    public function findVariantsByIds(array $productIds)
    {
        if (empty($productIds)) {
            return collect([]);
        }

        $products = $this->model->whereIn('id', $productIds)
            ->with([
                'variants' => function ($q) {
                    $q->select('id', 'product_id', 'sku', 'stock', 'low_stock_threshold', 'image', 'updated_at')
                        ->with(['optionValues.option']);
                },
                'gallery' => function ($q) {
                    $q->orderBy('sort_order', 'asc');
                }
            ])
            ->get();

        // Sort products by the order of IDs provided
        return collect($productIds)->map(function ($id) use ($products) {
            return $products->firstWhere('id', $id);
        })->filter()->values();
    }
    /**
     * Database fallback autocomplete search
     * Used when Elasticsearch is unavailable
     */
    public function autocomplete(string $query, int $limit = 10)
    {
        $query = trim($query);
        if (empty($query)) {
            return collect([]);
        }
        return $this->model->with(['store', 'gallery', 'category'])
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('slug', 'LIKE', "%{$query}%");
            })
            ->orderByRaw("CASE WHEN name LIKE '{$query}%' THEN 1 ELSE 2 END")
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get();
    }


    public function chunkProducts(int $chunkSize, callable $callback)
    {
        Product::with([
            'gallery',
            'reviews.images',
            'variants.optionValues',
            'options.values',
            'packages.items',
        ])->chunkById($chunkSize, $callback);
    }

    public function deleteInTransaction(callable $callback)
    {
        return DB::transaction($callback);
    }
    public function hydrateProductsForSearch(array $productIds)
    {
        if (empty($productIds)) {
            return collect([]);
        }

        $products = $this->model->whereIn('id', $productIds)
            ->with(['store', 'category', 'gallery'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->where('is_active', true);
            }], 'stock')
            ->get();

        // Sort products by their Elasticsearch relevance (same order as $productIds)
        return collect($productIds)->map(function ($id) use ($products) {
            return $products->firstWhere('id', $id);
        })->filter()->values();
    }
}
