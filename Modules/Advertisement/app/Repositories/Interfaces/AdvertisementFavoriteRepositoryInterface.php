<?php

namespace Modules\Advertisement\Repositories\Interfaces;

interface AdvertisementFavoriteRepositoryInterface
{
    public function toggle($userId, $advertisementId);

    public function getByUser($userId, $filters = []);

    public function isFavorited($userId, $advertisementId);

    public function getFavoritesCount($advertisementId);
}
