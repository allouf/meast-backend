<?php

namespace Modules\Services\Services;

use Modules\Services\Models\ServiceOfferReview;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferReviewService
{
    /**
     * Submit or update a review for a service offer (one review per user per service).
     *
     * @throws NotFoundHttpException if service not found or not public
     */
    public function submitReview(int $userId, int $serviceOfferId, int $rating, ?string $description = null): ServiceOfferReview
    {
        $offer = app(ServiceOfferService::class)->getPublicById($serviceOfferId);
        if (! $offer) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
        $review = ServiceOfferReview::firstOrNew([
            'user_id' => $userId,
            'service_offer_id' => $serviceOfferId,
        ]);
        $review->rating = $rating;
        $review->description = $description ? trim($description) : null;
        $review->save();
        return $review;
    }
}
