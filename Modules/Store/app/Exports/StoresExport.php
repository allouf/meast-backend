<?php

namespace Modules\Store\Exports;

use Modules\Store\Models\Store;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StoresExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * Return all stores (you can filter active ones if needed)
     */
    public function collection()
    {
        return Store::with(['user'])
            ->get();
    }

    /**
     * Define Excel headers
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Owner',
            'Country Code',
            'Email(s)',
            'Phone(s)',
            'Address',
            'Active',
            'Freezone',
            'Rating',
            'Created At',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($store): array
    {
        return [
            $store->id,
            $store->name,
            $store->slug,
            optional($store->user)->name,
            $store->country_code,
            is_array($store->emails) ? implode(', ', $store->emails) : $store->emails,
            is_array($store->phones) ? implode(', ', $store->phones) : $store->phones,
            $store->address,
            $store->is_active ? 'Active' : 'Inactive',
            $store->is_freezone ? 'Yes' : 'No',
            $store->rating,
            $store->created_at->format('Y-m-d H:i'),
        ];
    }
}
