<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\User\AddCartItemRequest;
use Modules\Store\Http\Requests\User\AddPackageToCartRequest;
use Modules\Store\Http\Requests\User\UpdateCartItemRequest;
use Modules\Store\Http\Resources\User\CartItemResource;
use Modules\Store\Http\Resources\User\CartResource;
use Modules\Store\Services\CartService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get my cart
     */
    public function getMyCart()
    {
        try {
            $userId = Auth::id();
            $cart = $this->cartService->getMyCart($userId);

            if (!$cart) {
                return $this->successResponse('Cart is empty', ['cart' => null]);
            }

            return $this->successResponse('Cart retrieved successfully', new CartResource($cart));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Add item to cart
     */
    public function addItem(AddCartItemRequest $request)
    {
        try {
            $userId = Auth::id();
            $data = $request->validated();

            $cartItem = $this->cartService->addItemToCart(
                $userId,
                $data['product_id'],
                $data['product_variant_id'] ?? null,
                $data['quantity']
            );

            $cartItem->load(['product', 'variant.product']);
            return $this->successResponse('Item added to cart successfully', new CartItemResource($cartItem));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(UpdateCartItemRequest $request, $itemId)
    {
        try {
            $userId = Auth::id();
            $data = $request->validated();

            $cartItem = $this->cartService->updateCartItemQuantity(
                $userId,
                $itemId,
                $data['quantity']
            );

            $cartItem->load(['product', 'variant.product']);
            return $this->successResponse('Cart item updated successfully', new CartItemResource($cartItem));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Add package to cart
     */
    public function addPackage(AddPackageToCartRequest $request)
    {
        try {
            $userId = Auth::id();
            $data = $request->validated();

            $cartItem = $this->cartService->addPackageToCart(
                $userId,
                $data['package_id'],
                $data['quantity']
            );

            $cartItem->load(['package.items.product', 'package.items.variant']);
            return $this->successResponse('Package added to cart successfully', new CartItemResource($cartItem));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Remove cart item
     */
    public function removeItem($itemId)
    {
        try {
            $userId = Auth::id();

            $deleted = $this->cartService->removeCartItem($userId, $itemId);

            if ($deleted) {
                return $this->successResponse('Item removed from cart successfully');
            }

            return $this->errorResponse('Failed to remove item from cart', 500);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function clearCart()
    {
        try {
            $userId = Auth::id();

            $this->cartService->clearCart($userId);

            return $this->successResponse('Cart cleared successfully');
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
