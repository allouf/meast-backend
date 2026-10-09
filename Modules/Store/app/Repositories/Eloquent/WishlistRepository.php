<?php

namespace Modules\Store\Repositories\Eloquent;

use Modules\Store\Models\Product;
use Modules\Store\Models\Wishlist;
use Modules\Store\Repositories\Interfaces\WishlistRepositoryInterface;

class WishlistRepository implements WishlistRepositoryInterface
{
    protected $model;

    public function __construct(Wishlist $model)
    {
        $this->model = $model;
    }

    public function toggleWishlist($userId, $productId)
    {
        $wishlist = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return ['added' => false];
        }

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId
        ]);

        return ['added' => true];
    }


    public function getWishlistByUserId($userId)
    {
        return Product::whereIn('id', function ($q) use ($userId) {
            $q->select('product_id')
                ->from('wishlists')
                ->where('user_id', $userId);
        })
        ->with(['gallery', 'category', 'store'])
        ->withSum(['variants as total_stock' => function ($q) {
            $q->where('is_active', true);
        }], 'stock')
        ->get();
    }


}
