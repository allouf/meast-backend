<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\User\WishlistToggleRequest;
use Modules\Store\Http\Resources\User\ProductListResource;
use Modules\Store\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class WishlistController extends Controller
{
    public function __construct(
        protected WishlistService  $wishlistService,
    ) {}


    public function toggle(WishlistToggleRequest $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return $this->errorResponse(__('store::messages.unauthorized'));
        }

        $result = $this->wishlistService->toggleWishlist(
            $userId,
            $request->product_id
        );
        return $this->successResponse(__($result['added'] ? 'store::messages.Product added to wishlist' : 'store::messages.Product removed from wishlist'));
    }

    public function getWishlistByUserId(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return $this->errorResponse(__('store::messages.unauthorized'));
        }

        $wishlist = $this->wishlistService->getWishlistByUserId($userId);

        return $this->successResponse(
            __('store::messages.success'),
            ProductListResource::collection($wishlist)
        );
    }

}
