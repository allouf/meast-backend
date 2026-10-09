<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'consumer'   => 'api',
            'freelancer' => 'api',
            'company'    => 'api',
            'admin'      => 'admin',
        ];

        foreach ($roles as $role => $guard) {
            Role::firstOrCreate(
                ['name' => $role, 'guard_name' => $guard]
            );
        }
    }
}
