<?php

namespace Modules\Store\Observers;

use Modules\Store\Utilities\ProductSlugHelper;
use Modules\Store\Models\ProductVariant;

class ProductVariantObserver
{
    public function updated(ProductVariant $productVariant): void
    {
        if (empty($productVariant->sku) || $productVariant->wasChanged('optionValues')) {
            $this->generateSku($productVariant);
        }
    }

    public function generateSkuAfterOptionValuesAttached(ProductVariant $productVariant): void
    {
        $this->generateSku($productVariant);
    }

    private function generateSku(ProductVariant $productVariant): void
    {
        if (!$productVariant->relationLoaded('optionValues')) {
            $productVariant->load(['optionValues.option']);
        }
        if (empty($productVariant->sku)) {
            $productName = $productVariant->product->name ?? 'Product';
            $optionValues = [];
            if ($productVariant->optionValues) {
                foreach ($productVariant->optionValues as $optionValue) {
                    $optionValues[] = $optionValue->value;
                }
            }
            $sku = ProductSlugHelper::generateVariantSku($productName, $optionValues, $productVariant->id);
            $sku = preg_replace('/\s*-\s*/', '-', $sku);
            $sku = preg_replace('/-+/', '-', $sku);
            $sku = trim($sku, '-');
            $count = ProductVariant::where('sku', $sku)->where('id', '!=', $productVariant->id)->count();
            if ($count > 0) {
                $sku .= '-' . ($count + 1);
            }
            $productVariant->updateQuietly(['sku' => $sku]);
        }
    }
}
