<?php

namespace Modules\Services\Services;

use Modules\Services\Models\ServiceOfferReport;

class ServiceOfferReportService
{
    /**
     * Submit a report for a service offer. Service must exist and be public (active).
     *
     * @return ServiceOfferReport
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function submitReport(int $userId, int $serviceOfferId, string $reason, ?string $description = null): ServiceOfferReport
    {
        $offer = app(ServiceOfferService::class)->getPublicById($serviceOfferId);
        if (! $offer) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
        return ServiceOfferReport::create([
            'user_id' => $userId,
            'service_offer_id' => $serviceOfferId,
            'reason' => $reason,
            'description' => $description ? trim($description) : null,
        ]);
    }
}
