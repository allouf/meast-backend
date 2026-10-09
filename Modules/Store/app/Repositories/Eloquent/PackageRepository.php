<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductPackage;
use Modules\Store\Models\ProductPackageItem;
use Modules\Store\Models\ProductVariant;
use Modules\Store\Repositories\Interfaces\PackageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PackageRepository implements PackageRepositoryInterface
{
    use PaginateTrait;

    public function __construct(
        protected ProductPackage $packageModel,
        protected ProductPackageItem $packageItemModel,
        protected Product $productModel,
        protected ProductVariant $variantModel
    ) {}

    public function index(array $filters)
    {
        $query = $this->packageModel
            ->with([
                'store',
                'product.store',
                'product.gallery',
                'product.options.values',
                'product.variants.optionValues',
                'items.product',
                'items.product.options.values',
                'items.product.variants.optionValues',
                'items.variant',
                'items.variant.optionValues'
            ])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['active_only'])) {
            $query->where('is_active', true);
        }

        if (!empty($filters['paginate'])) {
            return $this->paginate($query);
        }

        return [
            'data' => $query->get(),
            'meta' => null,
        ];
    }

    public function getByProduct(int $productId, bool $activeOnly = false): Collection
    {
        $query = $this->packageModel
            ->where('product_id', $productId)
            ->with([
                'store',
                'product.store',
                'product.gallery',
                'product.options.values',
                'product.variants.optionValues',
                'items.product',
                'items.product.options.values',
                'items.product.variants.optionValues',
                'items.variant',
                'items.variant.optionValues'
            ]);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function getByStore(int $storeId, bool $activeOnly = false, bool $paginate = true)
    {
        $query = $this->packageModel
            ->where('store_id', $storeId)
            ->with([
                'store',
                'product',
                'product.store',
                'product.gallery',
                'product.options.values',
                'product.variants.optionValues',
                'items.product',
                'items.product.options.values',
                'items.product.variants.optionValues',
                'items.variant',
                'items.variant.optionValues'
            ])
            ->orderBy('created_at', 'desc');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if ($paginate) {
            return $this->paginate($query);
        }

        return [
            'data' => $query->get(),
            'meta' => null,
        ];
    }


    public function findById(int $id): ProductPackage
    {
        return $this->packageModel->findOrFail($id);
    }

    public function create(array $data): ProductPackage
    {
        return $this->packageModel->create($data);
    }

    public function update(array $data, ProductPackage $package): ProductPackage
    {
        $package->update($data);
        return $package->fresh();
    }

    public function delete(ProductPackage $package): bool
    {
        return $package->delete();
    }

    public function attachItems(int $packageId, array $items): void
    {
        foreach ($items as $item) {
            $this->packageItemModel->create([
                'package_id' => $packageId,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
            ]);
        }
    }

    public function detachItems(int $packageId, array $itemIds): void
    {
        $this->packageItemModel
            ->where('package_id', $packageId)
            ->whereIn('id', $itemIds)
            ->delete();
    }

    public function syncItems(int $packageId, array $items): void
    {
        // Delete existing items
        $this->packageItemModel->where('package_id', $packageId)->delete();

        // Create new items
        $this->attachItems($packageId, $items);
    }

    public function findWithItems(int $id): ProductPackage
    {
        return $this->packageModel
            ->with([
                'store',
                'product.store',
                'product.gallery',
                'product.options.values',
                'product.variants.optionValues',
                'items.product',
                'items.product.category',
                'items.product.gallery',
                'items.product.options.values',
                'items.product.variants.optionValues',
                'items.variant',
                'items.variant.optionValues'
            ])
            ->findOrFail($id);
    }

    public function productExists(int $productId): bool
    {
        return $this->productModel->where('id', $productId)->exists();
    }

    public function variantExists(int $variantId): bool
    {
        return $this->variantModel->where('id', $variantId)->exists();
    }

    public function variantBelongsToProduct(int $variantId, int $productId): bool
    {
        return $this->variantModel
            ->where('id', $variantId)
            ->where('product_id', $productId)
            ->exists();
    }

    public function getProduct(int $productId): ?Product
    {
        return $this->productModel->find($productId);
    }


    public function toggleActivation(ProductPackage $package): ProductPackage
    {
        $package->update([
            'is_active' => ! $package->is_active
        ]);

        return $package->fresh();
    }
}
