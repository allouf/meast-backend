<?php

namespace Modules\Store\Services;

use Modules\Store\Models\Product;
use Modules\Store\Models\Coupon;
use Modules\Store\Models\CouponUsage;
use Modules\Store\Models\ProductVariant;
use Modules\Store\Repositories\Interfaces\CouponRepositoryInterface;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CouponService
{
    protected $couponRepository;

    public function __construct(CouponRepositoryInterface $couponRepository)
    {
        $this->couponRepository = $couponRepository;
    }

    public function index(array $filters = [])
    {
        return $this->couponRepository->index($filters);
    }


    public function findById($id)
    {
        return $this->couponRepository->findById($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            switch ($data['scope']) {
                case 'categories':
                    if (empty($data['categories']) || !is_array($data['categories'])) {
                        throw ValidationException::withMessages([
                            'categories' => __('store::messages.Categories are required when scope is "categories".'),
                        ]);
                    }
                    if (!empty($data['products'])) {
                        throw ValidationException::withMessages([
                            'products' => __('store::messages.Products should not be provided when scope is "categories".'),
                        ]);
                    }
                    break;

                case 'products':
                    if (empty($data['products']) || !is_array($data['products'])) {
                        throw ValidationException::withMessages([
                            'products' => __('store::messages.Products are required when scope is "products".'),
                        ]);
                    }

                    $storeId = $data['store_id'] ?? null;
                    if (!$storeId) {
                        throw ValidationException::withMessages([
                            'store_id' => __('store::messages.Store ID is required.'),
                        ]);
                    }

                    $productStoreIds = Product::whereIn('id', $data['products'])
                        ->pluck('store_id')
                        ->unique();

                    if ($productStoreIds->count() > 1 || !$productStoreIds->contains($storeId)) {
                        throw ValidationException::withMessages([
                            'products' => __('store::messages.All products must belong to the same store.'),
                        ]);
                    }

                    $data['category_id'] = null;
                    break;

                case 'all':
                    $data['category_id'] = null;
                    $data['products'] = [];
                    break;
            }

            $coupon = $this->couponRepository->create(
                collect($data)->except(['products', 'categories'])->toArray()
            );

            if (!empty($data['categories'])) {
                $coupon->categories()->attach($data['categories']);
            }

            if (isset($data['products']) && is_array($data['products'])) {
                $coupon->products()->attach($data['products']);
            }

            return $coupon->load('products', 'categories');
        });
    }

    public function update(array $data, $id)
    {
        $coupon = $this->couponRepository->findById($id);
        throw_if(!$coupon, ModelNotFoundException::class, "Record not found");
        return $this->couponRepository->update($data, $coupon);
    }

    public function delete($id)
    {
        $coupon = $this->couponRepository->findById($id);
        throw_if(!$coupon, ModelNotFoundException::class, "Record not found");

        if ($coupon->usages()->exists()) {
            throw new HttpException(400, "Cannot delete coupon that has been used.");
        }

        return $this->couponRepository->delete($coupon);
    }

    public function getByStoreId($storeId, ?string $search = null, ?string $status = null)
    {
        return $this->couponRepository->getByStoreId($storeId, $search, $status);
    }



    public function validateCoupon(string $couponCode, array $items, int $userId): array
    {
        $coupon = Coupon::with('products')->where('code', $couponCode)->first();

        if (!$coupon) {
            throw new NotFoundHttpException('Coupon code not found.');
        }

        if ($coupon->isExpired()) {
            throw new HttpException(400, 'This coupon has expired.');
        }

        $totalUsages = CouponUsage::where('coupon_id', $coupon->id)->count();
        if ($coupon->max_usage_count !== null && $totalUsages >= $coupon->max_usage_count) {
            throw new HttpException(400, 'This coupon has reached its maximum usage limit.');
        }

        $userUsages = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->count();
        if ($coupon->max_usage_per_user !== null && $userUsages >= $coupon->max_usage_per_user) {
            throw new HttpException(400, 'You have reached the maximum usage limit for this coupon.');
        }

        $itemsByStore = [];
        $storeSubtotals = [];

        foreach ($items as $item) {
            $variantId = $item['product_variant_id'];
            $quantity = $item['quantity'];

            $variant = ProductVariant::with('product')->find($variantId);
            if (!$variant || !$variant->product) {
                continue;
            }

            $storeId = $variant->product->store_id;
            if (!isset($itemsByStore[$storeId])) {
                $itemsByStore[$storeId] = [];
                $storeSubtotals[$storeId] = 0;
            }

            $price = $variant->price;
            $subtotal = $price * $quantity;
            $storeSubtotals[$storeId] += $subtotal;

            $itemsByStore[$storeId][] = [
                'variant' => $variant,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        $applicableStores = [];
        $totalDiscount = 0;
        $discountDetails = [];

        foreach ($itemsByStore as $storeId => $storeItems) {
            if ($coupon->store_id != $storeId) {
                continue;
            }

            $storeSubtotal = $storeSubtotals[$storeId];

            if ($coupon->min_cart_amount !== null && $storeSubtotal < $coupon->min_cart_amount) {
                throw new HttpException(
                    400,
                    "Minimum cart amount for this coupon is {$coupon->min_cart_amount}. Your cart total for this store is {$storeSubtotal}."
                );
            }

            $storeDiscount = 0;
            $hasMatchingItems = false;

            if ($coupon->scope === 'all') {
                if ($coupon->discount_type === 'percentage') {
                    $storeDiscount = ($storeSubtotal * $coupon->discount_value) / 100;
                } else {
                    $storeDiscount = min($coupon->discount_value, $storeSubtotal);
                }
                $hasMatchingItems = true;
            } elseif ($coupon->scope === 'categories') {
                foreach ($storeItems as $itemData) {
                    $variant = $itemData['variant'];
                    if ($variant->product && $variant->product->category_id == $coupon->category_id) {
                        $hasMatchingItems = true;
                        $itemPrice = $itemData['price'];
                        $itemQuantity = $itemData['quantity'];

                        if ($coupon->discount_type === 'percentage') {
                            $itemDiscount = ($itemPrice * $coupon->discount_value) / 100;
                        } else {
                            $itemDiscount = min($coupon->discount_value, $itemPrice);
                        }

                        $storeDiscount += $itemDiscount * $itemQuantity;
                    }
                }
            } elseif ($coupon->scope === 'products') {
                $couponProductIds = $coupon->products->pluck('id')->toArray();
                foreach ($storeItems as $itemData) {
                    $variant = $itemData['variant'];
                    if ($variant->product && in_array($variant->product->id, $couponProductIds)) {
                        $hasMatchingItems = true;
                        $itemPrice = $itemData['price'];
                        $itemQuantity = $itemData['quantity'];

                        if ($coupon->discount_type === 'percentage') {
                            $itemDiscount = ($itemPrice * $coupon->discount_value) / 100;
                        } else {
                            $itemDiscount = min($coupon->discount_value, $itemPrice);
                        }

                        $storeDiscount += $itemDiscount * $itemQuantity;
                    }
                }
            }

            if (!$hasMatchingItems) {
                $scopeMessage = match ($coupon->scope) {
                    'categories' => 'No items in your cart match the coupon category.',
                    'products' => 'No items in your cart match the coupon products.',
                    default => 'Coupon cannot be applied to your cart.',
                };
                throw new HttpException(400, $scopeMessage);
            }

            $applicableStores[] = $storeId;
            $totalDiscount += $storeDiscount;
            $discountDetails[] = [
                'store_id' => $storeId,
                'store_subtotal' => $storeSubtotal,
                'discount' => $storeDiscount,
                'final_amount' => max(0, $storeSubtotal - $storeDiscount),
            ];
        }

        if (empty($applicableStores)) {
            throw new HttpException(400, 'This coupon does not apply to any items in your cart.');
        }

        return [
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'scope' => $coupon->scope,
            ],
            'total_discount' => $totalDiscount,
            'discount_details' => $discountDetails,
            'applicable_stores' => $applicableStores,
        ];
    }
}
