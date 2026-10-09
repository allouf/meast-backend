<?php

namespace Modules\Store\Services;

use Modules\Store\Enums\ProductStatus;
use Modules\Store\Repositories\Interfaces\CartRepositoryInterface;
use Modules\Store\Models\Cart;
use Modules\Store\Models\CartItem;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductVariant;
use Modules\Store\Models\ProductPackage;
use Modules\Store\Services\PackageService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class CartService
{
    protected $cartRepository;
    protected $packageService;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        PackageService $packageService
    ) {
        $this->cartRepository = $cartRepository;
        $this->packageService = $packageService;
    }

    public function getOrCreateCart(int $userId): Cart
    {
        $cart = $this->cartRepository->findCartByUserId($userId);

        if (!$cart) {
            $cart = $this->cartRepository->createCart([
                'user_id' => $userId,
                'total_price' => 0,
                'is_active' => true
            ]);
        }

        return $cart;
    }

    public function addItemToCart(int $userId, int $productId, ?int $variantId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $productId, $variantId, $quantity) {
            $cart = $this->getOrCreateCart($userId);

            $product = Product::find($productId);
            if (!$product) {
                throw new NotFoundHttpException('Product not found.');
            }

            if ($product->status->value !== ProductStatus::ACTIVE->value) {
                throw new HttpException(400, 'This product is not available.');
            }

            $price = $product->price;
            $variant = null;

            if ($variantId) {
                $variant = ProductVariant::with('product')->find($variantId);
                if (!$variant) {
                    throw new NotFoundHttpException('Product variant not found.');
                }

                if ($variant->product_id !== $productId) {
                    throw new HttpException(400, 'Variant does not belong to this product.');
                }

                if (!$variant->is_active) {
                    throw new HttpException(409, 'This product variant is out of stock.');
                }

                if ($variant->stock <= 0) {
                    throw new HttpException(409, 'This product variant is out of stock.');
                }

                if ($variant->stock < $quantity) {
                    throw new HttpException(409, 'Insufficient stock available.');
                }

                $price = $variant->price;

                $existingItem = $this->cartRepository->findCartItem($cart->id, $variantId);
            } else {
                $existingItem = $this->cartRepository->findCartItemByProduct($cart->id, $productId);
            }

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $quantity;

                if ($variant && $variant->stock < $newQuantity) {
                    throw new HttpException(409, 'Insufficient stock available.');
                }

                $storedPrice = $existingItem->price;
                $newSubtotal = $newQuantity * $storedPrice;
                $existingItem->update([
                    'quantity' => $newQuantity,
                    'subtotal' => $newSubtotal
                ]);

                $this->updateCartTotalPrice($cart->id);

                return $existingItem->fresh();
            }

            $item = $this->cartRepository->createCartItem([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $quantity * $price
            ]);

            $this->updateCartTotalPrice($cart->id);

            return $item;
        });
    }

    public function updateCartItemQuantity(int $userId, int $itemId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $itemId, $quantity) {
            $cart = $this->getOrCreateCart($userId);
            $cartItem = $this->cartRepository->findCartItemById($itemId);

            if (!$cartItem) {
                throw new NotFoundHttpException('Cart item not found.');
            }

            if ($cartItem->cart_id !== $cart->id) {
                throw new HttpException(403, 'Cart item does not belong to your cart.');
            }

            if ($quantity <= 0) {
                throw new HttpException(400, 'Quantity must be greater than 0.');
            }

            if ($cartItem->product_variant_id) {
                $variant = ProductVariant::find($cartItem->product_variant_id);
                if (!$variant) {
                    throw new NotFoundHttpException('Product variant not found.');
                }
            }

            $cartItem->update([
                'quantity' => $quantity,
                'subtotal' => $quantity * $cartItem->price
            ]);

            $this->updateCartTotalPrice($cart->id);

            return $cartItem->fresh();
        });
    }

    public function removeCartItem(int $userId, int $itemId): bool
    {
        return DB::transaction(function () use ($userId, $itemId) {
            $cart = $this->getOrCreateCart($userId);
            $cartItem = $this->cartRepository->findCartItemById($itemId);

            if (!$cartItem) {
                throw new NotFoundHttpException('Cart item not found.');
            }

            if ($cartItem->cart_id !== $cart->id) {
                throw new HttpException(403, 'Cart item does not belong to your cart.');
            }

            $deleted = $this->cartRepository->deleteCartItem($cartItem);

            if ($deleted) {
                $this->updateCartTotalPrice($cart->id);
            }

            return $deleted;
        });
    }

    public function addPackageToCart(int $userId, int $packageId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $packageId, $quantity) {
            $cart = $this->getOrCreateCart($userId);

            $package = ProductPackage::find($packageId);
            if (!$package) {
                throw new NotFoundHttpException('Package not found.');
            }

            if (!$package->is_active) {
                throw new HttpException(400, 'This package is not available.');
            }

            $priceAfter = $this->packageService->calculatePriceAfter($packageId);

            $existingItem = $this->cartRepository->findCartItemByPackage($cart->id, $packageId);

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $quantity;
                $newSubtotal = $newQuantity * $priceAfter;

                $existingItem->update([
                    'quantity' => $newQuantity,
                    'price' => $priceAfter,
                    'subtotal' => $newSubtotal
                ]);

                $this->updateCartTotalPrice($cart->id);

                return $existingItem->fresh();
            }

            $item = $this->cartRepository->createCartItem([
                'cart_id' => $cart->id,
                'package_id' => $packageId,
                'product_id' => null,
                'product_variant_id' => null,
                'quantity' => $quantity,
                'price' => $priceAfter,
                'subtotal' => $quantity * $priceAfter
            ]);

            $this->updateCartTotalPrice($cart->id);

            return $item;
        });
    }

    public function getMyCart(int $userId): ?Cart
    {
        $cart = $this->cartRepository->findCartByUserId($userId);

        if ($cart) {
            return $this->cartRepository->getCartWithItems($cart->id);
        }

        return null;
    }

    protected function updateCartTotalPrice(int $cartId): void
    {
        $cart = $this->cartRepository->getCartWithItems($cartId);

        if ($cart) {
            $total = $cart->items->sum('subtotal');
            $this->cartRepository->updateCartTotal($cartId, $total);
        }
    }
    public function clearCart(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $cart = $this->cartRepository->findCartByUserId($userId);

            if (!$cart) {
                return;
            }

            $this->cartRepository->deleteAllCartItems($cart->id);

            $this->cartRepository->updateCartTotal($cart->id, 0);
        });
    }
}
