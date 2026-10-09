<?php

namespace Modules\Store\Jobs;

use Modules\Store\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Store\Library\Elasticsearch\StoreElasticsearch;

class SyncStoreToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Store $store;

    /**
     * Create a new job instance.
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $es = new StoreElasticsearch();
        $es->indexStore($this->store);

        Log::info('Store synced to Elasticsearch', [
            'store_id' => $this->store->id,
            'name' => $this->store->name,
        ]);
    }
}

