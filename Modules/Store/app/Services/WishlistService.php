<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\WishlistRepositoryInterface;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

class WishlistService
{
    protected $wishlistRepository;

    public function __construct(WishlistRepositoryInterface $wishlistRepository)
    {
        $this->wishlistRepository = $wishlistRepository;
    }

    public function toggleWishlist($userId, $productId)
    {
        return $this->wishlistRepository->toggleWishlist($userId, $productId);
    }

    public function getWishlistByUserId($userId)
    {
        return $this->wishlistRepository->getWishlistByUserId($userId);
    }

}
