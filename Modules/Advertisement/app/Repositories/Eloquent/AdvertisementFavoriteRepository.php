<?php

namespace Modules\Advertisement\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Advertisement\Models\AdvertisementFavorite;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementFavoriteRepositoryInterface;

class AdvertisementFavoriteRepository implements AdvertisementFavoriteRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(AdvertisementFavorite $model)
    {
        $this->model = $model;
    }

    public function toggle($userId, $advertisementId)
    {
        $favorite = $this->model->where('user_id', $userId)->where('advertisement_id', $advertisementId)->first();

        if ($favorite) {
            $favorite->delete();
            return ['added' => false];
        }

        $this->model->create(['user_id' => $userId, 'advertisement_id' => $advertisementId]);
        return ['added' => true];
    }

    public function getByUser($userId, $filters = [])
    {
        $query = $this->model->with(['advertisement.user', 'advertisement.category', 'advertisement.subcategory'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function isFavorited($userId, $advertisementId)
    {
        return $this->model->where('user_id', $userId)->where('advertisement_id', $advertisementId)->exists();
    }

    public function getFavoritesCount($advertisementId)
    {
        return $this->model->where('advertisement_id', $advertisementId)->count();
    }
}
