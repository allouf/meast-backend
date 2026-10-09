<?php

namespace Modules\Store\Repositories\Eloquent;

use Modules\Store\Models\Cart;
use Modules\Store\Models\CartItem;
use Modules\Store\Repositories\Interfaces\CartRepositoryInterface;

class CartRepository implements CartRepositoryInterface
{
    public function __construct(
        protected Cart $model,
        protected CartItem $cartItemModel
    ) {}

    public function findCartByUserId(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    public function createCart(array $data): Cart
    {
        return $this->model->create($data);
    }

    public function findCartItemById(int $itemId): ?CartItem
    {
        return $this->cartItemModel->find($itemId);
    }

    public function findCartItem(int $cartId, int $variantId): ?CartItem
    {
        return $this->cartItemModel
            ->where('cart_id', $cartId)
            ->where('product_variant_id', $variantId)
            ->first();
    }

    public function findCartItemByProduct(int $cartId, int $productId): ?CartItem
    {
        return $this->cartItemModel
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->whereNull('package_id')
            ->first();
    }

    public function findCartItemByPackage(int $cartId, int $packageId): ?CartItem
    {
        return $this->cartItemModel
            ->where('cart_id', $cartId)
            ->where('package_id', $packageId)
            ->first();
    }

    public function createCartItem(array $data): CartItem
    {
        return $this->cartItemModel->create($data);
    }

    public function updateCartItem(array $data, CartItem $cartItem): CartItem
    {
        $cartItem->update($data);
        return $cartItem;
    }

    public function deleteCartItem(CartItem $cartItem): bool
    {
        return $cartItem->delete();
    }

    public function updateCartTotal(int $cartId, float $total): bool
    {
        return $this->model->where('id', $cartId)->update(['total_price' => $total]);
    }

    public function getCartWithItems(int $cartId): ?Cart
    {
        return $this->model->with([
            'items.product',
            'items.product.store',
            'items.product.category.ancestors',
            'items.product.gallery',
            'items.variant.product',
            'items.variant.optionValues.option',
            'items.package.items.product',
            'items.package.items.variant',
            'items.package.items.variant.optionValues.option',
        ])->find($cartId);
    }
    public function deleteAllCartItems(int $cartId): bool
    {
        return $this->cartItemModel
            ->where('cart_id', $cartId)
            ->delete();
    }
}
