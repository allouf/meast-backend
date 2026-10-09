<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\Cart;
use Modules\Store\Models\CartItem;

interface CartRepositoryInterface
{
    public function findCartByUserId(int $userId);
    public function createCart(array $data);
    public function findCartItemById(int $itemId);
    public function findCartItem(int $cartId, int $variantId);
    public function findCartItemByProduct(int $cartId, int $productId);
    public function findCartItemByPackage(int $cartId, int $packageId);
    public function createCartItem(array $data);
    public function updateCartItem(array $data, CartItem $cartItem);
    public function deleteCartItem(CartItem $cartItem);
    public function updateCartTotal(int $cartId, float $total);
    public function getCartWithItems(int $cartId);
    public function deleteAllCartItems(int $cartId): bool;

}

