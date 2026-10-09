<?php

namespace Modules\Services\Repositories\Interfaces;

interface ServiceOfferReviewRepositoryInterface
{
    /**
     * Get aggregate review stats for all offers of a provider.
     *
     * @return array{average_rating: float, total_reviews: int}
     */
    public function getStatsByServiceProviderId(int $serviceProviderId): array;
}
