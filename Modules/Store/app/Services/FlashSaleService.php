<?php

namespace Modules\Store\Services;

use Modules\Store\Models\FlashSale;
use Modules\Store\Repositories\Interfaces\FlashSaleRepositoryInterface;
use Modules\Store\Repositories\Interfaces\ProductRepositoryInterface;
use Modules\Store\Services\StoreMemberPermissionService;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\DB;

class FlashSaleService
{
    protected $flashSaleRepository;
    protected $productRepository;
    protected $permissionService;

    public function __construct(
        FlashSaleRepositoryInterface $flashSaleRepository,
        ProductRepositoryInterface $productRepository,
        StoreMemberPermissionService $permissionService
    ) {
        $this->flashSaleRepository = $flashSaleRepository;
        $this->productRepository = $productRepository;
        $this->permissionService = $permissionService;
    }

    public function all(array $filters = [])
    {
        return $this->flashSaleRepository->all($filters);
    }

    public function findById($id)
    {
        return $this->flashSaleRepository->findById($id);
    }

    public function validateProductsForFlashSale(array $products): void
    {
        foreach ($products as $productData) {
            $product = $this->productRepository->findById($productData['product_id']);
            $this->permissionService->requirePermission($product->store_id, 'products.pricing');
        }
    }

    public function createFlashSale(array $data)
    {
        $this->validateProductsForFlashSale($data['products']);

        return DB::transaction(function () use ($data) {
            $flashSaleData = collect($data)->except('products')->toArray();
            $flashSale = $this->flashSaleRepository->create($flashSaleData);

            foreach ($data['products'] as $productData) {
                foreach ($productData['variants'] as $variantId) {
                    $flashSale->variants()->attach($variantId);
                }
            }

            return $flashSale->load('variants.product');
        });
    }

    public function updateFlashSale(array $data, FlashSale $flashSale)
    {
        if (isset($data['products'])) {
            $this->validateProductsForFlashSale($data['products']);
        }

        return DB::transaction(function () use ($data, $flashSale) {
            $flashSaleData = collect($data)->except('products')->toArray();
            $flashSale = $this->flashSaleRepository->update($flashSaleData, $flashSale);

            if (isset($data['products'])) {
                $variantIds = collect($data['products'])
                    ->pluck('variants')
                    ->flatten()
                    ->unique()
                    ->values()
                    ->all();

                $flashSale->variants()->sync($variantIds);
            }

            return $flashSale->load('variants.product');
        });
    }

    public function validateFlashSalePermissions(FlashSale $flashSale): void
    {
        foreach ($flashSale->variants as $variant) {
            $this->permissionService->requirePermission($variant->product->store_id, 'products.pricing');
        }
    }

    public function delete($id)
    {
        $flashSale = $this->flashSaleRepository->findById($id);
        throw_if(!$flashSale, ModelNotFoundException::class, "Record not found");

        $this->validateFlashSalePermissions($flashSale);

        return $this->flashSaleRepository->delete($flashSale);
    }

    public function getFlashSalesByStore(int $storeId, $paginate = true, ?string $search = null, array $filters = [])
    {
        $result = $this->flashSaleRepository->getByStoreId($storeId, $paginate, $search,$filters);

        $result['data'] = $result['data']->map(function ($flashSale) {
            return $this->formatFlashSale($flashSale);
        });

        return $result;
    }


    public function findByIdWithStock($id)
    {
        $flashSale = $this->flashSaleRepository->findById($id);

        if (!$this->flashSaleRepository->hasAvailableStock($flashSale)) {
            throw new NotFoundHttpException(__('store::messages.Flash Sale has no available stock'));
        }

        return $this->flashSaleRepository->loadVariantsWithStock($flashSale);
    }

    private function formatFlashSale($flashSale)
    {
        $products = $flashSale->variants
            ->groupBy(fn($variant) => $variant->product->id)
            ->map(function ($variants, $productId) {
                $product = $variants->first()->product;

                return [
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'variants' => $variants->map(fn($variant) => [
                        'id'    => $variant->id,
                        'sku'   => $variant->sku,
                        'price' => (float) $variant->price,
                    ])->values(),
                ];
            })->values();

        $flashSale->products = $products;
        unset($flashSale->variants);
        return $flashSale;
    }
}
