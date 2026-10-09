<?php

namespace Modules\Services\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Services\Library\ServiceOfferElasticsearch;
use Modules\Services\Models\ServiceOffer;

class SyncServiceOfferToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ServiceOffer $serviceOffer
    ) {}

    public function handle(): void
    {
        $es = new ServiceOfferElasticsearch();
        $es->indexDocumentFromModel($this->serviceOffer);
    }
}
