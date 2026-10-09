<?php

namespace Modules\Store\Services;

use Modules\Store\Enums\MasterOrderStatus;
use Modules\Store\Enums\StoreOrderStatus;
use Modules\Store\Enums\ProductStatus;
use Modules\Store\Repositories\Interfaces\MasterOrderRepositoryInterface;
use Modules\Store\Repositories\Interfaces\StoreOrderRepositoryInterface;
use Modules\Store\Models\MasterOrder;
use Modules\Store\Models\StoreOrder;
use Modules\Store\Models\UserAddress;
use Modules\Store\Models\Coupon;
use Modules\Store\Models\CouponUsage;
use Modules\Store\Models\FlashSaleProduct;
use Modules\Store\Models\OrderItem;
use Modules\Store\Models\OrderPackage;
use Modules\Store\Models\ProductVariant;
use Modules\Store\Models\ProductPackage;
use Modules\Store\Models\StoreMember;
use Modules\Store\Enums\StoreMemberRole;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderService
{
    protected $masterOrderRepository;
    protected $storeOrderRepository;

    public function __construct(
        MasterOrderRepositoryInterface $masterOrderRepository,
        StoreOrderRepositoryInterface $storeOrderRepository
    ) {
        $this->masterOrderRepository = $masterOrderRepository;
        $this->storeOrderRepository = $storeOrderRepository;
    }

    public function createOrder(int $userId, array $data): MasterOrder
    {
        return DB::transaction(function () use ($userId, $data) {
            $this->validateUserAddress($userId, $data['user_address_id']);

            $itemsByStore    = $this->validateAndPrepareItems($data['items'] ?? []);
            $packagesByStore = $this->validateAndPreparePackages($data['packages'] ?? []);

            if (empty($itemsByStore) && empty($packagesByStore)) {
                throw new HttpException(400, 'Order must contain items or packages.');
            }
            $coupon = $this->validateCoupon($data['coupon_code'] ?? null, $itemsByStore);

            $masterOrder = $this->createMasterOrder($userId);
            $this->createStoreOrders($masterOrder, $itemsByStore, $packagesByStore, $coupon, $userId, $data);

            $this->recalculateMasterOrderTotal($masterOrder);

            return $this->masterOrderRepository->getMasterOrderWithStoreOrders($masterOrder->id);
        });
    }

    protected function validateUserAddress(int $userId, int $addressId): void
    {
        $address = UserAddress::where('id', $addressId)
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            throw new NotFoundHttpException('Address not found or does not belong to you.');
        }
    }

    protected function validateAndPrepareItems(array $items): array
    {
        $itemsByStore = [];
        $stockIssues = [];

        foreach ($items as $item) {
            $variant = ProductVariant::with('product')
                ->lockForUpdate()
                ->find($item['product_variant_id']);

            if (!$variant) {
                $stockIssues[] = "Product variant (ID: {$item['product_variant_id']}) not found.";
                continue;
            }

            $issue = $this->validateVariant($variant, $item['quantity']);
            if ($issue) {
                $stockIssues[] = $issue;
                continue;
            }

            $storeId = $variant->product->store_id;
            if (!isset($itemsByStore[$storeId])) {
                $itemsByStore[$storeId] = [];
            }

            $price = $this->calculateItemPrice($variant, $storeId);
            $subtotal = $price * $item['quantity'];

            $itemsByStore[$storeId][] = [
                'variant' => $variant,
                'variant_id' => $variant->id,
                'quantity' => $item['quantity'],
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        if (!empty($stockIssues)) {
            throw new HttpException(400, implode(', ', $stockIssues));
        }

        return $itemsByStore;
    }

    protected function validateVariant(ProductVariant $variant, int $quantity): ?string
    {
        $productName = $variant->product ? $variant->product->name : 'Unknown Product';

        if (!$variant->is_active) {
            return "Product variant '{$productName}' is not available.";
        }

        if (!$variant->product || $variant->product->status->value !== ProductStatus::ACTIVE->value) {
            return "Product '{$productName}' is not available.";
        }

        if (!$variant->product->store_id) {
            return "Product '{$productName}' does not belong to any store.";
        }

        if ($variant->stock < $quantity) {
            return "Insufficient stock for product '{$productName}'. Available: {$variant->stock}, Requested: {$quantity}";
        }

        return null;
    }

    protected function calculateItemPrice(ProductVariant $variant, int $storeId): float
    {
        $basePrice = (float) $variant->price;

        $flashSale = $this->getActiveFlashSale($variant->id, $storeId);

        if ($flashSale) {
            return $this->applyFlashSaleDiscount($basePrice, $flashSale);
        }

        return $basePrice;
    }

    protected function getActiveFlashSale(int $variantId, int $storeId): ?object
    {
        $flashSaleProduct = FlashSaleProduct::where('variant_id', $variantId)
            ->whereHas('flashSale', function ($query) use ($storeId) {
                $query->where('store_id', $storeId)
                    ->where('status', 'active')
                    ->where('start_date', '<=', Carbon::now())
                    ->where('end_date', '>', Carbon::now());
            })
            ->with('flashSale')
            ->first();

        return $flashSaleProduct?->flashSale;
    }

    protected function applyFlashSaleDiscount(float $price, object $flashSale): float
    {
        if ($flashSale->discount_type === 'percentage') {
            $discountAmount = ($price * $flashSale->discount_value) / 100;
            return max(0, $price - $discountAmount);
        }

        return max(0, $price - $flashSale->discount_value);
    }

    protected function validateAndPreparePackages(array $packages): array
    {
        $packagesByStore = [];
        $issues = [];

        foreach ($packages as $pkg) {
            $package = ProductPackage::with(['store', 'items.variant'])
                ->where('is_active', true)
                ->find($pkg['package_id']);

            if (!$package) {
                $issues[] = "Package (ID: {$pkg['package_id']}) not found or inactive.";
                continue;
            }

            $storeId = $package->store_id;
            $quantity = $pkg['quantity'];

            foreach ($package->items as $item) {
                if (!$item->variant || !$item->variant->is_active) {
                    $issues[] = "Variant in package '{$package->title}' is not available.";
                    continue;
                }

                $required = $item->quantity * $quantity;

                if ($item->variant->stock < $required) {
                    $issues[] =
                        "Insufficient stock for product '{$item->variant->product->name}' " .
                        "in package '{$package->title}'.";
                }
            }

            $price = $this->calculatePackagePrice($package);
            $subtotal = $price * $quantity;

            $packagesByStore[$storeId][] = [
                'package' => $package,
                'package_id' => $package->id,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        if (!empty($issues)) {
            throw new HttpException(400, implode(', ', $issues));
        }

        return $packagesByStore;
    }

    protected function calculatePackagePrice(ProductPackage $package): float
    {
        $basePrice = $package->items->sum(function ($item) {
            return $item->variant->price * $item->quantity;
        });

        if ($package->discount_percent) {
            return $basePrice * (1 - ($package->discount_percent / 100));
        }

        if ($package->fixed_discount) {
            return max(0, $basePrice - $package->fixed_discount);
        }

        return $basePrice;
    }

    protected function validateCoupon(?string $couponCode, array $itemsByStore): ?Coupon
    {
        if (empty($couponCode)) {
            return null;
        }

        $coupon = Coupon::with('products')->where('code', $couponCode)->first();

        if (!$coupon) {
            throw new NotFoundHttpException("Coupon code '{$couponCode}' not found.");
        }

        $couponAppliesToStore = collect($itemsByStore)->keys()->contains($coupon->store_id);

        if (!$couponAppliesToStore) {
            throw new HttpException(400, "This coupon does not apply to any items in your cart.");
        }

        return $coupon;
    }

    protected function createMasterOrder(int $userId): MasterOrder
    {
        return $this->masterOrderRepository->create([
            'user_id' => $userId,
            'total_price' => 0,
            'status' => MasterOrderStatus::PENDING_PAYMENT->value,
        ]);
    }

    protected function calculateTotalPrice(array $itemsByStore): float
    {
        $total = 0;
        foreach ($itemsByStore as $storeItems) {
            foreach ($storeItems as $itemData) {
                $total += $itemData['subtotal'];
            }
        }
        return $total;
    }

    protected function createStoreOrders(
        MasterOrder $masterOrder,
        array $itemsByStore,
        array $packagesByStore,
        ?Coupon $coupon,
        int $userId,
        array $data
    ): void {

        $storeIds = array_unique(array_merge(
            array_keys($itemsByStore),
            array_keys($packagesByStore)
        ));

        foreach ($storeIds as $storeId) {
            $storeItems    = $itemsByStore[$storeId] ?? [];
            $storePackages = $packagesByStore[$storeId] ?? [];

            $storeSubtotal = $this->calculateStoreSubtotal($storeItems);
            $couponResult = $this->applyCouponToStore($coupon, $storeId, $storeSubtotal, $storeItems, $userId);

            $storeOrder = $this->createStoreOrder(
                $masterOrder->id,
                $storeId,
                0,
                $data
            );

            $this->createOrderItems($storeOrder, $couponResult['items']);
            $this->createOrderPackages($storeOrder, $storePackages);

            // 5️⃣ subtotal النهائي
            $finalSubtotal =
                $this->calculateStoreSubtotal($couponResult['items'])
                + collect($storePackages)->sum('subtotal');

            $storeOrder->update(['subtotal' => $finalSubtotal]);
            if ($couponResult['should_create_coupon_usage'] ?? false) {
                $this->createCouponUsage($couponResult['coupon_id'], $storeOrder->id, $userId);
            }
        }
    }

    protected function calculateStoreSubtotal(array $storeItems): float
    {
        return collect($storeItems)->sum('subtotal');
    }

    protected function applyCouponToStore(
        ?Coupon $coupon,
        int $storeId,
        float $storeSubtotal,
        array $storeItems,
        int $userId
    ): array {
        if (!$coupon || $coupon->store_id != $storeId) {
            return [
                'final_subtotal' => $storeSubtotal,
                'items' => $storeItems,
                'should_create_coupon_usage' => false,
            ];
        }

        if (!$this->isCouponValid($coupon, $storeSubtotal, $userId)) {
            return [
                'final_subtotal' => $storeSubtotal,
                'items' => $storeItems,
                'should_create_coupon_usage' => false,
            ];
        }

        $result = $this->calculateCouponDiscount($coupon, $storeSubtotal, $storeItems, $userId);

        return [
            'final_subtotal' => $result['final_subtotal'],
            'items' => $result['items'],
            'should_create_coupon_usage' => $result['should_create_coupon_usage'],
            'coupon_id' => $coupon->id,
        ];
    }

    protected function isCouponValid(Coupon $coupon, float $storeSubtotal, int $userId): bool
    {
        if ($coupon->isExpired()) {
            return false;
        }

        $totalUsages = CouponUsage::where('coupon_id', $coupon->id)->count();
        if ($coupon->max_usage_count !== null && $totalUsages >= $coupon->max_usage_count) {
            return false;
        }

        $userUsages = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->count();
        if ($coupon->max_usage_per_user !== null && $userUsages >= $coupon->max_usage_per_user) {
            return false;
        }

        if ($coupon->min_cart_amount !== null && $storeSubtotal < $coupon->min_cart_amount) {
            return false;
        }

        return true;
    }

    protected function calculateCouponDiscount(Coupon $coupon, float $storeSubtotal, array $storeItems, int $userId): array
    {
        $couponDiscount = 0;
        $finalItems = $storeItems;

        switch ($coupon->scope) {
            case 'all':
                $couponDiscount = $this->calculateDiscountAmount($coupon, $storeSubtotal);
                $finalSubtotal = max(0, $storeSubtotal - $couponDiscount);
                break;

            case 'categories':
                $result = $this->applyCategoryCoupon($coupon, $finalItems);
                $couponDiscount = $result['discount'];
                $finalItems = $result['items'];
                $finalSubtotal = $this->calculateStoreSubtotal($finalItems);
                break;

            case 'products':
                $result = $this->applyProductCoupon($coupon, $finalItems);
                $couponDiscount = $result['discount'];
                $finalItems = $result['items'];
                $finalSubtotal = $this->calculateStoreSubtotal($finalItems);
                break;

            default:
                $finalSubtotal = $storeSubtotal;
        }

        // Return flag to create coupon usage after store order is created
        return [
            'final_subtotal' => $finalSubtotal,
            'items' => $finalItems,
            'should_create_coupon_usage' => $couponDiscount > 0,
            'coupon_id' => $coupon->id,
        ];
    }

    protected function calculateDiscountAmount(Coupon $coupon, float $amount): float
    {
        if ($coupon->discount_type === 'percentage') {
            return ($amount * $coupon->discount_value) / 100;
        }

        return min($coupon->discount_value, $amount);
    }

    protected function applyCategoryCoupon(Coupon $coupon, array $items): array
    {
        $totalDiscount = 0;
        $hasMatchingItems = false;

        foreach ($items as &$itemData) {
            $variant = $itemData['variant'];

            if ($variant->product && $variant->product->category_id == $coupon->category_id) {
                $hasMatchingItems = true;
                $itemDiscount = $this->calculateDiscountAmount($coupon, $itemData['price']);

                $itemData['price'] = max(0, $itemData['price'] - $itemDiscount);
                $itemData['subtotal'] = $itemData['price'] * $itemData['quantity'];
                $totalDiscount += $itemDiscount * $itemData['quantity'];
            }
        }

        return [
            'discount' => $hasMatchingItems ? $totalDiscount : 0,
            'items' => $items,
        ];
    }

    protected function applyProductCoupon(Coupon $coupon, array $items): array
    {
        $totalDiscount = 0;
        $hasMatchingItems = false;
        $couponProductIds = $coupon->products->pluck('id')->toArray();

        foreach ($items as &$itemData) {
            $variant = $itemData['variant'];

            if ($variant->product && in_array($variant->product->id, $couponProductIds)) {
                $hasMatchingItems = true;
                $itemDiscount = $this->calculateDiscountAmount($coupon, $itemData['price']);

                $itemData['price'] = max(0, $itemData['price'] - $itemDiscount);
                $itemData['subtotal'] = $itemData['price'] * $itemData['quantity'];
                $totalDiscount += $itemDiscount * $itemData['quantity'];
            }
        }

        return [
            'discount' => $hasMatchingItems ? $totalDiscount : 0,
            'items' => $items,
        ];
    }

    protected function createOrderPackages(StoreOrder $storeOrder, array $packages): void
    {
        foreach ($packages as $pkg) {
            $orderPackage = OrderPackage::create([
                'store_order_id' => $storeOrder->id,
                'product_package_id' => $pkg['package_id'],
                'quantity' => $pkg['quantity'],
                'price' => $pkg['price'],
                'subtotal' => $pkg['subtotal'],
            ]);

            $this->explodePackageToOrderItems(
                $orderPackage,
                $pkg['package'],
                $pkg['quantity'],
                $storeOrder
            );
        }
    }

    protected function explodePackageToOrderItems(
        OrderPackage $orderPackage,
        ProductPackage $package,
        int $packageQuantity,
        StoreOrder $storeOrder
    ): void {

        $baseTotal = $package->items->sum(function ($item) {
            return $item->variant->price * $item->quantity;
        });

        if ($baseTotal <= 0) {
            return;
        }

        foreach ($package->items as $packageItem) {
            $variant = $packageItem->variant;

            $baseItemPrice =
                $variant->price * $packageItem->quantity;

            $ratio = $baseItemPrice / $baseTotal;

            $allocatedSubtotal =
                $orderPackage->subtotal * $ratio;

            $unitPrice =
                $allocatedSubtotal /
                ($packageItem->quantity * $packageQuantity);

            OrderItem::create([
                'store_order_id' => $storeOrder->id,
                'product_variant_id' => $variant->id,
                'quantity' => $packageItem->quantity * $packageQuantity,
                'price' => round($unitPrice, 2),
                'subtotal' => round($allocatedSubtotal, 2),
                'is_from_package' => true,
                'order_package_id' => $orderPackage->id,
            ]);

            $variant->decrement(
                'stock',
                $packageItem->quantity * $packageQuantity
            );
        }
    }


    protected function createStoreOrder(int $masterOrderId, int $storeId, float $subtotal, array $data): StoreOrder
    {
        return $this->storeOrderRepository->create([
            'master_order_id' => $masterOrderId,
            'store_id' => $storeId,
            'user_address_id' => $data['user_address_id'],
            'subtotal' => $subtotal,
            'payment_method' => $data['payment_method'] ?? 'cod',
            'shipping_method' => $data['shipping_method'] ?? 'standard',
            'status' => StoreOrderStatus::PLACED->value,
        ]);
    }

    protected function createOrderItems(StoreOrder $storeOrder, array $items): void
    {
        foreach ($items as $itemData) {
            OrderItem::create([
                'store_order_id' => $storeOrder->id,
                'product_variant_id' => $itemData['variant_id'],
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
                'subtotal' => $itemData['subtotal'],
            ]);

            $itemData['variant']->decrement('stock', $itemData['quantity']);
        }
    }

    /**
     * Create coupon usage record after store order is successfully created
     *
     * @param int $couponId
     * @param int $storeOrderId
     * @param int $userId
     * @return CouponUsage
     * @throws HttpException
     */
    protected function createCouponUsage(int $couponId, int $storeOrderId, int $userId): CouponUsage
    {
        // Verify the store order exists
        $storeOrder = StoreOrder::find($storeOrderId);
        if (!$storeOrder) {
            throw new HttpException(500, "Store order with ID {$storeOrderId} not found when creating coupon usage.");
        }

        // Check if coupon usage already exists for this combination
        $existingUsage = CouponUsage::where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->where('order_id', $storeOrderId)
            ->first();

        if ($existingUsage) {
            return $existingUsage;
        }

        // Create new coupon usage
        return CouponUsage::create([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'order_id' => $storeOrderId,
            'used_at' => Carbon::now(),
        ]);
    }

    protected function recalculateMasterOrderTotal(MasterOrder $masterOrder): void
    {
        $storeOrders = $this->storeOrderRepository->getStoreOrdersByMasterOrderId($masterOrder->id);
        $totalPrice = $storeOrders->sum('subtotal');

        $masterOrder->update(['total_price' => $totalPrice]);
    }

    public function getMyOrders(int $userId)
    {
        return $this->masterOrderRepository->getMasterOrdersByUserId($userId);
    }

    public function getOrdersByStoreId(int $storeId, int $userId, array $filters = [])
    {
        $storeOwner = StoreMember::where('store_id', $storeId)
            ->where('user_id', $userId)
            ->where('role', StoreMemberRole::STORE_OWNER)
            ->where('status', 'active')
            ->first();

        if (!$storeOwner) {
            throw new HttpException(403, 'You do not have permission to view orders for this store. Only store owners can view orders.');
        }

        return $this->storeOrderRepository->getStoreOrdersByStoreId($storeId, $filters);
    }

    public function getAllMasterOrders(array $filters = [])
    {
        if (!auth()->user()->hasRole('admin')) {
            throw new HttpException(403, 'Unauthorized.');
        }

        return $this->masterOrderRepository->getAllMasterOrders($filters);
    }

    public function getOrderStatisticsByStoreId(int $storeId, int $userId): array
    {
        $storeOwner = StoreMember::where('store_id', $storeId)
            ->where('user_id', $userId)
            ->where('role', StoreMemberRole::STORE_OWNER)
            ->where('status', 'active')
            ->first();

        if (!$storeOwner) {
            throw new HttpException(403, 'You do not have permission to view statistics for this store. Only store owners can view statistics.');
        }

        return $this->storeOrderRepository->getOrderStatisticsByStoreId($storeId, $userId);
    }

    public function getOrderById(int $orderId, int $userId)
    {
        $masterOrder = $this->masterOrderRepository->findById($orderId);

        if ($masterOrder) {
            if ($masterOrder->user_id !== $userId) {
                throw new HttpException(403, 'You do not have permission to view this order.');
            }
            return $this->masterOrderRepository->getMasterOrderWithStoreOrders($orderId);
        }

        $storeOrder = $this->storeOrderRepository->findById($orderId);

        if (!$storeOrder) {
            throw new NotFoundHttpException('Order not found.');
        }

        $isBuyer = $storeOrder->masterOrder->user_id === $userId;
        $isStoreOwner = StoreMember::where('store_id', $storeOrder->store_id)
            ->where('user_id', $userId)
            ->where('role', StoreMemberRole::STORE_OWNER)
            ->where('status', 'active')
            ->exists();

        if (!$isBuyer && !$isStoreOwner) {
            throw new HttpException(403, 'You do not have permission to view this order.');
        }

        return $this->storeOrderRepository->getStoreOrderWithItems($orderId);
    }

    public function updateOrderStatusByBuyer(int $orderId, int $userId, string $status)
    {
        throw new HttpException(400, 'This method needs to be updated for the new order structure.');
    }

    public function updateOrderStatusBySeller(int $orderId, int $userId, string $status)
    {
        throw new HttpException(400, 'This method needs to be updated for the new order structure.');
    }
    public function updateOrderStatus(int $orderId, string $status)
    {
        return $this->masterOrderRepository->update($orderId, ['status' => $status]);
    }

    public function getMasterOrderDetails(int $masterOrderId)
    {
        if (!auth()->user()->hasRole('admin')) {
            throw new HttpException(403, 'Unauthorized.');
        }

        $order = $this->masterOrderRepository->getMasterOrderWithStoreOrders($masterOrderId);

        if (!$order) {
            throw new HttpException(404, 'Master order not found.');
        }

        return $order;
    }
}
