<?php

namespace Modules\Services\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Repositories\Interfaces\ProviderProjectRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferReviewRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceProviderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProviderProfileService
{
    public function __construct(
        protected ServiceProviderRepositoryInterface $serviceProviderRepository,
        protected ServiceOfferReviewRepositoryInterface $serviceOfferReviewRepository,
        protected ServiceOfferService $serviceOfferService,
        protected ProviderProjectRepositoryInterface $providerProjectRepository
    ) {}

    /**
     * Get provider public "about" (with review stats and verified flag).
     *
     * @throws NotFoundHttpException
     */
    public function getAbout(int $id): ServiceProvider
    {
        $provider = $this->serviceProviderRepository->findById($id, ['user', 'languages']);
        if (! $provider) {
            throw new NotFoundHttpException(__('services::messages.service_provider.not_found'));
        }

        $stats = $this->serviceOfferReviewRepository->getStatsByServiceProviderId($provider->id);
        $provider->setAttribute('average_rating', (float) ($provider->avg_rating ?? 0));
        $provider->setAttribute('total_reviews', $stats['total_reviews']);

        return $provider;
    }

    /**
     * Get provider's service offers (paginated).
     *
     * @throws NotFoundHttpException
     * @return LengthAwarePaginator<\Modules\Services\Models\ServiceOffer>
     */
    public function getServices(int $id, array $filters): LengthAwarePaginator
    {
        $provider = $this->serviceProviderRepository->findById($id);
        if (! $provider) {
            throw new NotFoundHttpException(__('services::messages.service_provider.not_found'));
        }

        return $this->serviceOfferService->getByProvider($provider, $filters);
    }

    /**
     * Get provider's portfolio projects (paginated).
     *
     * @throws NotFoundHttpException
     * @return LengthAwarePaginator<\Modules\Services\Models\ProviderProject>
     */
    public function getPortfolio(int $id, array $filters): LengthAwarePaginator
    {
        $provider = $this->serviceProviderRepository->findById($id);
        if (! $provider) {
            throw new NotFoundHttpException(__('services::messages.service_provider.not_found'));
        }

        return $this->providerProjectRepository->getPaginatedByServiceProviderId(
            $provider->id,
            $filters,
            []
        );
    }
}
