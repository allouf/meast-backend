<?php

namespace Modules\Core\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Core\Models\User;

class ConsumerExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $users = User::role('consumer')
            ->get(['id', 'name', 'email', 'phone', 'country_code', 'region', 'is_active', 'created_at']);

        return $users->map(function ($user) {
            return [
                'ID'           => $user->id,
                'Name'         => $user->name,
                'Email'        => $user->email,
                'Phone'        => $user->phone,
                'Country Code' => $user->country_code,
                'Region'       => $user->region,
                'Active'       => $user->is_active ? 'Yes' : 'No',
                'Created At'   => $user->created_at->format('Y-m-d H:i:s'),
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
            'Country Code',
            'Region',
            'Active',
            'Created At',
        ];
    }
}
