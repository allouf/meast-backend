<?php

namespace Modules\Store\Exports;

use Modules\Store\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoryExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Category::with('parent')->orderBy('sort_order')->get();
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->getTranslation('name', 'en'),
            $category->getTranslation('name', 'ar'),
            $category->icon,
            $category->sort_order,
            $category->is_active ? 'Active' : 'Inactive',
            $category->parent ? $category->parent->getTranslation('name', 'en') : null, 
            $category->parent ? $category->parent->getTranslation('name', 'ar') : null, 
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name (EN)',
            'Name (AR)',
            'Icon',
            'Sort Order',
            'Status',
            'Parent Name (EN)',
            'Parent Name (AR)',
        ];
    }
}
