<?php

namespace Modules\Store\Observers;

use Modules\Store\Jobs\SyncProductToElasticsearch;
use Modules\Store\Library\Elasticsearch\ProductElasticsearch;
use Modules\Store\Utilities\ProductSlugHelper;
use Modules\Store\Models\Product;

class ProductObserver
{
    protected $es;

    public function __construct()
    {
        $this->es = new ProductElasticsearch();
    }
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->generateSlug($product);
        SyncProductToElasticsearch::dispatch($product);
    }
    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        if ($product->wasChanged('name') && (empty($product->sku) || empty($product->slug))) {
            $this->generateSlug($product);
        }
        SyncProductToElasticsearch::dispatch($product);
    }
    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product)
    {
        $this->es->deleteDocument('products', $product->id);
    }
    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void {}
    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void {}
    /**
     * Generate slug for the product
     */
    private function generateSlug(Product $product): void
    {
        $updates = [];

        if (empty($product->slug)) {
            $baseName = preg_replace('/\s*\(Copy\)$/i', '', $product->name);

            $slug = ProductSlugHelper::generateSlug($baseName, $product->id);

            $count = Product::where('slug', $slug)->where('id', '!=', $product->id)->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }

            $updates['slug'] = $slug;
        }

        if (!empty($updates)) {
            $product->updateQuietly($updates);
        }
    }
}
