<?php

namespace Modules\Services\Observers;

use Modules\Services\Jobs\SyncServiceOfferToElasticsearch;
use Modules\Services\Library\ServiceOfferElasticsearch;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Services\BoostCampaignService;

class ServiceOfferObserver
{
    public function created(ServiceOffer $serviceOffer): void
    {
        SyncServiceOfferToElasticsearch::dispatch($serviceOffer);
    }

    public function updated(ServiceOffer $serviceOffer): void
    {
        if (
            $serviceOffer->wasChanged(['is_active', 'status'])
            && ! $serviceOffer->isEligibleForPaidBoostPurchase()
        ) {
            app(BoostCampaignService::class)->terminateActiveBoostsForOffer(
                $serviceOffer,
                'offer_no_longer_boost_eligible'
            );
        }

        SyncServiceOfferToElasticsearch::dispatch($serviceOffer);
    }

    public function deleted(ServiceOffer $serviceOffer): void
    {
        (new ServiceOfferElasticsearch())->deleteDocumentById($serviceOffer->id);
    }
}
