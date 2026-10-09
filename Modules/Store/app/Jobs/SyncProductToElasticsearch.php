<?php

namespace Modules\Store\Jobs;

use Modules\Store\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Store\Library\Elasticsearch\ProductElasticsearch;

class SyncProductToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;

    /**
     * Create a new job instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $es = new ProductElasticsearch();
        $es->indexProduct($this->product);

        Log::info('Product synced to Elasticsearch', [
            'product_id' => $this->product->id,
            'name' => $this->product->name,
        ]);
    }
}
