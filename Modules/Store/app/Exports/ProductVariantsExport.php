<?php

namespace Modules\Store\Exports;

use Modules\Store\Models\ProductVariant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductVariantsExport implements FromCollection, WithHeadings
{
 
    public function collection()
    {
        return ProductVariant::with('product')
            ->get()
            ->map(function ($variant) {
                return [
                    'Product Name' => $variant->product->name ?? '',
                    'SKU' => $variant->sku,
                    'Price' => $variant->price,
                    'Stock' => $variant->stock,
                    'low_stock_threshold'=> $variant->low_stock_threshold,
                ];
            });
    }

 
    public function headings(): array
    {
        return [
            'Product Name',
            'SKU',
            'Price',
            'Stock',
            'Low Stock Threshold'
        ];
    }
}
