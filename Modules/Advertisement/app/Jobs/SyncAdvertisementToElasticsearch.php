<?php

namespace Modules\Advertisement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Advertisement\Library\AdvertisementElasticsearch;
use Modules\Advertisement\Models\Advertisement;

class SyncAdvertisementToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Advertisement $advertisement
    ) {}

    public function handle(): void
    {
        $es = new AdvertisementElasticsearch();
        $es->indexAdvertisement($this->advertisement);

        Log::info('Advertisement synced to Elasticsearch', [
            'advertisement_id' => $this->advertisement->id,
            'title' => $this->advertisement->title,
        ]);
    }
}
