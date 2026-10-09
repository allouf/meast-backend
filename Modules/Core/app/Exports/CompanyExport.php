<?php

namespace Modules\Core\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Core\Models\User;

class CompanyExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $users = User::role('company')
            ->with('company')
            ->get();

        return $users->map(function ($user) {
            return [
                'ID'               => $user->id,
                'Name'             => $user->name,
                'Email'            => $user->email,
                'Phone'            => $user->phone,
                'Company Name'     => $user->company?->company_name,
                'Registration No.' => $user->company?->registration_no,
                'VAT/Tax ID'       => $user->company?->vat_tax_id,
                'Website'          => $user->company?->website,
                'Active'           => $user->is_active ? 'Yes' : 'No',
                'Created At'       => $user->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Company Name',
            'Registration No.',
            'VAT/Tax ID',
            'Website',
            'Active',
            'Created At',
        ];
    }
}
