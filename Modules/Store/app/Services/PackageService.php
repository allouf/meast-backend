<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\PackageRepositoryInterface;
use Modules\Store\Models\ProductPackage;
use Modules\Store\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PackageService
{
    public function __construct(
        protected PackageRepositoryInterface $packageRepository
    ) {}


    public function index(array $filters)
    {
        return $this->packageRepository->index($filters);
    }

    public function getByProduct(int $productId, bool $activeOnly = false): Collection
    {
        if (!$this->packageRepository->productExists($productId)) {
            throw new NotFoundHttpException('Product not found.');
        }

        return $this->packageRepository->getByProduct($productId, $activeOnly);
    }

    public function getByStore(int $storeId, bool $activeOnly = false, bool $paginate = true)
    {
        return $this->packageRepository->getByStore($storeId, $activeOnly, $paginate);
    }

    public function findById(int $id): ProductPackage
    {
        return $this->packageRepository->findWithItems($id);
    }

    public function create(int $productId, array $data): ProductPackage
    {
        if (!$this->packageRepository->productExists($productId)) {
            throw new NotFoundHttpException('Product not found.');
        }

        $this->validateDiscountFields($data);

        if (isset($data['items']) && is_array($data['items'])) {
            $this->validateItems($data['items'], $productId);
        }

        return DB::transaction(function () use ($productId, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $product = $this->packageRepository->getProduct($productId);
            if (!$product) {
                throw new NotFoundHttpException('Product not found.');
            }

            $data['product_id'] = $productId;
            $data['store_id'] = $product->store_id;
            $package = $this->packageRepository->create($data);

            if (!empty($items)) {
                $this->packageRepository->attachItems($package->id, $items);
            }

            return $this->packageRepository->findWithItems($package->id);
        });
    }

    public function update(int $id, array $data): ProductPackage
    {
        $package = $this->packageRepository->findById($id);

        if (isset($data['discount_percent']) || isset($data['fixed_discount'])) {
            $this->validateDiscountFields($data);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $this->validateItems($data['items'], $package->product_id);
        }

        return DB::transaction(function () use ($package, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            $package = $this->packageRepository->update($data, $package);

            if ($items !== null) {
                $this->packageRepository->syncItems($package->id, $items);
            }

            return $this->packageRepository->findWithItems($package->id);
        });
    }

    public function delete(int $id): bool
    {
        $package = $this->packageRepository->findById($id);
        return $this->packageRepository->delete($package);
    }

    public function attachItems(int $packageId, array $items): ProductPackage
    {
        $package = $this->packageRepository->findById($packageId);
        $this->validateItems($items, $package->product_id);

        DB::transaction(function () use ($packageId, $items) {
            $this->packageRepository->attachItems($packageId, $items);
        });

        return $this->packageRepository->findWithItems($packageId);
    }

    public function detachItems(int $packageId, array $itemIds): ProductPackage
    {
        $this->packageRepository->findById($packageId);

        DB::transaction(function () use ($packageId, $itemIds) {
            $this->packageRepository->detachItems($packageId, $itemIds);
        });

        return $this->packageRepository->findWithItems($packageId);
    }

    public function calculatePriceBefore(int $packageId): float
    {
        $package = $this->packageRepository->findWithItems($packageId);
        $total = 0;

        if ($package->product) {
            $mainProductPrice = $package->product->final_price ?? $package->product->price;
            $total += (float) $mainProductPrice;
        }

        foreach ($package->items as $item) {
            $product = $item->product;
            if ($item->variant) {
                $price = (float) $item->variant->price;
            } else {
                $price = (float) ($product->final_price ?? $product->price);
            }
            $total += $price * $item->quantity;
        }

        return round($total, 2);
    }

    public function calculatePriceAfter(int $packageId): float
    {
        $package = $this->packageRepository->findWithItems($packageId);
        $priceBefore = $this->calculatePriceBefore($packageId);

        if ($package->discount_percent) {
            $discount = $priceBefore * ($package->discount_percent / 100);
            return round($priceBefore - $discount, 2);
        }

        if ($package->fixed_discount) {
            return round(max($priceBefore - $package->fixed_discount, 0), 2);
        }

        return $priceBefore;
    }

    public function calculateSavings(int $packageId): float
    {
        $priceBefore = $this->calculatePriceBefore($packageId);
        $priceAfter = $this->calculatePriceAfter($packageId);
        return round($priceBefore - $priceAfter, 2);
    }

    public function calculateSavingsPercent(int $packageId): ?float
    {
        $priceBefore = $this->calculatePriceBefore($packageId);

        if ($priceBefore <= 0) {
            return null;
        }

        $savings = $this->calculateSavings($packageId);
        $savingsPercent = ($savings / $priceBefore) * 100;

        return round($savingsPercent, 2);
    }

    protected function validateDiscountFields(array $data): void
    {
        $hasPercent = !empty($data['discount_percent']);
        $hasFixed = !empty($data['fixed_discount']);

        if ($hasPercent && $hasFixed) {
            throw new HttpException(400, 'Cannot set both discount_percent and fixed_discount. Please choose one.');
        }

        if ($hasPercent && ($data['discount_percent'] < 0 || $data['discount_percent'] > 100)) {
            throw new HttpException(400, 'Discount percent must be between 0 and 100.');
        }

        if ($hasFixed && $data['fixed_discount'] < 0) {
            throw new HttpException(400, 'Fixed discount cannot be negative.');
        }
    }

    protected function validateItems(array $items, int $mainProductId): void
    {
        if (empty($items)) {
            throw new HttpException(400, 'Package must have at least one item.');
        }

        foreach ($items as $item) {
            if (!$this->packageRepository->productExists($item['product_id'])) {
                throw new NotFoundHttpException("Product ID {$item['product_id']} not found.");
            }

            if (!empty($item['variant_id'])) {
                if (!$this->packageRepository->variantExists($item['variant_id'])) {
                    throw new NotFoundHttpException("Variant ID {$item['variant_id']} not found.");
                }

                if (!$this->packageRepository->variantBelongsToProduct($item['variant_id'], $item['product_id'])) {
                    throw new HttpException(400, "Variant ID {$item['variant_id']} does not belong to product ID {$item['product_id']}.");
                }
            }

            if (empty($item['quantity']) || $item['quantity'] < 1) {
                throw new HttpException(400, 'Item quantity must be at least 1.');
            }
        }
    }

    public function toggleActivation(int $packageId): ProductPackage
    {
        $package = $this->packageRepository->findById($packageId);

        if (! $package) {
            throw new NotFoundHttpException('Package not found.');
        }

        if (! $package->is_active && $package->items()->count() === 0) {
            throw new HttpException(400, 'Cannot activate an empty package.');
        }

        return $this->packageRepository->toggleActivation($package);
    }
}
