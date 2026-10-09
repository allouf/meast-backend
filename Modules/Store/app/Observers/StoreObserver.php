<?php

namespace Modules\Store\Observers;

use Modules\Store\Jobs\SyncStoreToElasticsearch;
use Modules\Store\Library\Elasticsearch\StoreElasticsearch;
use Modules\Store\Utilities\ProductSlugHelper;
use Illuminate\Support\Facades\Log;
use Modules\Store\Models\Store;

class StoreObserver
{
    protected $es;

    public function __construct()
    {
        $this->es = new StoreElasticsearch();
    }

    public function creating(Store $store): void
    {
        $this->generateSlug($store);
    }

    public function created(Store $store): void
    {
        SyncStoreToElasticsearch::dispatch($store);
    }

    public function updated(Store $store): void
    {
        if ($store->wasChanged('name')) {
            $this->generateSlugForUpdate($store);
        }
        SyncStoreToElasticsearch::dispatch($store);
    }

    public function deleted(Store $store): void
    {
        $this->es->deleteDocument('stores', $store->id, true);
    }

    public function restored(Store $store): void {}

    public function forceDeleted(Store $store): void {}

    private function generateSlug(Store $store): void
    {
        if (empty($store->slug)) {
            $baseSlug = ProductSlugHelper::generateSlug($store->name, null);
            $slug = $baseSlug;
            $counter = 1;

            while (Store::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $store->slug = $slug;
        }
    }

    private function generateSlugForUpdate(Store $store): void
    {
        $baseSlug = ProductSlugHelper::generateSlug($store->name, $store->id);
        $slug = $baseSlug;
        $counter = 1;

        while (Store::where('slug', $slug)->where('id', '!=', $store->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        if ($store->slug !== $slug) {
            $store->updateQuietly(['slug' => $slug]);
        }
    }
}
