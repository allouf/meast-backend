<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\Wishlist;
interface WishlistRepositoryInterface
{
    public function toggleWishlist($userId, $productId);
    public function getWishlistByUserId($userId);

}
