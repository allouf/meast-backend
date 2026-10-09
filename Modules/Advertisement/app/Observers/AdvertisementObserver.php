<?php

namespace Modules\Advertisement\Observers;

use Modules\Advertisement\Jobs\SyncAdvertisementToElasticsearch;
use Modules\Advertisement\Library\AdvertisementElasticsearch;
use Modules\Advertisement\Models\Advertisement;

class AdvertisementObserver
{
    public function created(Advertisement $advertisement): void
    {
        SyncAdvertisementToElasticsearch::dispatch($advertisement);
    }

    public function updated(Advertisement $advertisement): void
    {
        SyncAdvertisementToElasticsearch::dispatch($advertisement);
    }

    public function deleted(Advertisement $advertisement): void
    {
        $es = new AdvertisementElasticsearch();
        $es->deleteDocument('advertisements', $advertisement->id, true);
    }
}
