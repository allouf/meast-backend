<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Services\Http\Resources\User\ProviderProfileAboutResource;
use Modules\Services\Http\Resources\User\ProviderProjectResource;
use Modules\Services\Http\Resources\User\ServiceOfferListResource;
use Modules\Services\Services\ProviderProfileService;

class ProviderProfileController extends Controller
{
    public function __construct(
        protected ProviderProfileService $providerProfileService
    ) {}

    /**
     * Get provider public "about" (bio, languages, rating, location, verification).
     * GET api/v1/providers/{id}/about
     */
    public function about(int $id)
    {
        $provider = $this->providerProfileService->getAbout($id);

        return $this->successResponse(
            __('services::messages.service_provider.retrieved_successfully'),
            new ProviderProfileAboutResource($provider)
        );
    }

    /**
     * List provider's service offers (paginated, search, sort).
     * GET api/v1/providers/{id}/services?search=&sort=&page=&per_page=
     */
    public function services(Request $request, int $id)
    {
        $filters = $request->only([
            'search',
            'sort',
            'category_id',
            'service_category_id',
            'delivery_model',
            'type',
            'page',
            'per_page',
        ]);
        $paginator = $this->providerProfileService->getServices($id, $filters);

        return $this->successResponse(
            __('services::messages.service_offers.list_retrieved_successfully'),
            [
                'services' => ServiceOfferListResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]
        );
    }

    /**
     * List provider's portfolio projects (paginated, search).
     * GET api/v1/providers/{id}/portfolio?search=&page=&per_page=
     */
    public function portfolio(Request $request, int $id)
    {
        $filters = $request->only(['search', 'page', 'per_page']);
        $paginator = $this->providerProfileService->getPortfolio($id, $filters);

        return $this->successResponse(
            __('services::messages.provider_project.retrieved_successfully'),
            [
                'projects' => ProviderProjectResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]
        );
    }
}
