<?php

namespace Modules\Advertisement\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementFavoriteRepositoryInterface;

class AdvertisementFavoriteService
{
    public function __construct(
        protected AdvertisementFavoriteRepositoryInterface $favoriteRepository
    ) {}

    public function toggle($advertisementId)
    {
        return $this->favoriteRepository->toggle(Auth::id(), $advertisementId);
    }

    public function getMyFavorites($filters = [])
    {
        return $this->favoriteRepository->getByUser(Auth::id(), $filters);
    }

    public function isFavorited($advertisementId)
    {
        return $this->favoriteRepository->isFavorited(Auth::id(), $advertisementId);
    }

    public function getFavoritesCount($advertisementId)
    {
        return $this->favoriteRepository->getFavoritesCount($advertisementId);
    }
}
