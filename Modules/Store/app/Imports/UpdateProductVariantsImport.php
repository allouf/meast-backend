<?php

namespace Modules\Store\Imports;

use Modules\Store\Models\ProductVariant;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UpdateProductVariantsImport implements ToModel, WithHeadingRow
{
   
    public function model(array $row)
    {
        $variant = ProductVariant::where('sku', $row['sku'])->first();

        if ($variant) {
            $variant->update([
                'price' => $row['price'] ?? $variant->price,
                'stock' => $row['stock'] ?? $variant->stock,
            ]);
        }
        return null;
    }
}
