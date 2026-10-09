<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Models\ServiceOfferReview;
use Modules\Services\Repositories\Interfaces\ServiceOfferReviewRepositoryInterface;

class ServiceOfferReviewRepository implements ServiceOfferReviewRepositoryInterface
{
    public function __construct(
        protected ServiceOfferReview $model
    ) {}

    /** @inheritdoc */
    public function getStatsByServiceProviderId(int $serviceProviderId): array
    {
        $stats = $this->model->newQuery()
            ->whereHas('serviceOffer', fn ($q) => $q->where('service_provider_id', $serviceProviderId))
            ->selectRaw('COUNT(*) as total_reviews, COALESCE(AVG(rating), 0) as average_rating')
            ->first();

        return [
            'average_rating' => (float) round((float) ($stats->average_rating ?? 0), 2),
            'total_reviews' => (int) ($stats->total_reviews ?? 0),
        ];
    }
}
