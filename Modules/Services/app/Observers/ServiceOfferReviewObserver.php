<?php

namespace Modules\Services\Observers;

use Modules\Services\Models\ServiceOffer;
use Modules\Services\Models\ServiceOfferReview;
use Modules\Services\Models\ServiceProvider;

class ServiceOfferReviewObserver
{
    public function created(ServiceOfferReview $review): void
    {
        $this->recalculateProviderAvgRatingForReview($review);
    }

    public function updated(ServiceOfferReview $review): void
    {
        $originalOfferId = $review->getOriginal('service_offer_id');
        if ($originalOfferId && (int) $originalOfferId !== (int) $review->service_offer_id) {
            $this->recalculateProviderAvgRatingForServiceOfferId((int) $originalOfferId);
        }

        $this->recalculateProviderAvgRatingForReview($review);
    }

    public function deleted(ServiceOfferReview $review): void
    {
        $this->recalculateProviderAvgRatingForReview($review);
    }

    public function restored(ServiceOfferReview $review): void
    {
        $this->recalculateProviderAvgRatingForReview($review);
    }

    private function recalculateProviderAvgRatingForReview(ServiceOfferReview $review): void
    {
        $serviceOfferId = (int) ($review->service_offer_id ?? 0);
        if ($serviceOfferId <= 0) {
            return;
        }

        $this->recalculateProviderAvgRatingForServiceOfferId($serviceOfferId);
    }

    private function recalculateProviderAvgRatingForServiceOfferId(int $serviceOfferId): void
    {
        $providerId = (int) ServiceOffer::query()
            ->whereKey($serviceOfferId)
            ->value('service_provider_id');

        if ($providerId <= 0) {
            return;
        }

        $avg = (float) ServiceOfferReview::query()
            ->whereHas('serviceOffer', fn ($q) => $q->where('service_provider_id', $providerId))
            ->avg('rating');

        ServiceProvider::query()
            ->whereKey($providerId)
            ->update(['avg_rating' => round($avg ?: 0.0, 2)]);
    }
}

