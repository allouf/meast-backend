<?php

namespace Modules\Advertisement\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Advertisement\Http\Requests\User\AdvertisementFavoriteRequest;
use Modules\Advertisement\Http\Resources\User\AdvertisementFavoriteResource;
use Modules\Advertisement\Services\AdvertisementFavoriteService;

class AdvertisementFavoriteController extends Controller
{
    public function __construct(
        protected AdvertisementFavoriteService $favoriteService
    ) {}

    public function toggle(AdvertisementFavoriteRequest $request)
    {
        $result = $this->favoriteService->toggle($request->advertisement_id);
        return $this->successResponse(
            $result['added']
                ? __('advertisement::messages.advertisement_favorites.added_successfully')
                : __('advertisement::messages.advertisement_favorites.removed_successfully'),
            $result
        );
    }

    public function index(Request $request)
    {
        $filters = $request->only(['per_page']);
        $favorites = $this->favoriteService->getMyFavorites($filters);
        if (is_array($favorites) && isset($favorites['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisement_favorites.retrieved_successfully'), [
                'data' => AdvertisementFavoriteResource::collection($favorites['data']),
                'meta' => $favorites['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisement_favorites.retrieved_successfully'), AdvertisementFavoriteResource::collection($favorites));
    }

    public function check($advertisementId)
    {
        $isFavorited = $this->favoriteService->isFavorited($advertisementId);
        $count = $this->favoriteService->getFavoritesCount($advertisementId);
        return $this->successResponse(__('advertisement::messages.advertisement_favorites.check_successfully'), [
            'is_favorited' => $isFavorited,
            'favorites_count' => $count
        ]);
    }
}
