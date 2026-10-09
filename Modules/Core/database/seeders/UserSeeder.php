<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\Models\Admin;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'bilal.mardini1999@gmail.com'],
            [
                'country_code' => 'SY',
                'region' => 'Damascus',
                'name' => 'Bilal Mardini',
                'phone' => '0969616331',
                'password' => Hash::make('developer123'),
                'photo' => null,
                'provider' => 'email',
                'provider_id' => null,
                'provider_token' => null,
                'provider_refresh_token' => null,
                'verified_at' => now(),
                'ref_code' => Str::upper(Str::random(6)),
                'referred_by_user_id' => null,
                'is_active' => true,
                'is_profile_complete' => true,
            ]
        );

        $consumerRole = Role::firstOrCreate(['name' => 'consumer', 'guard_name' => 'api']);
        if (!$user->hasRole('consumer')) {
            $user->assignRole($consumerRole);
        }

        $users = [
            [
                'country_code' => 'SY',
                'region' => 'Damascus',
                'name' => 'Another User',
                'email' => 'another@example.com',
                'phone' => '0987654321',
                'password' => Hash::make('developer123'),
                'photo' => null,
                'provider' => 'email',
                'provider_id' => null,
                'provider_token' => null,
                'provider_refresh_token' => null,
                'verified_at' => now(),
                'ref_code' => Str::upper(Str::random(6)),
                'referred_by_user_id' => $user->id,
                'is_active' => true,
                'is_profile_complete' => true,
            ],
        ];

        foreach ($users as $userData) {
            $newUser = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            if (!$newUser->hasRole('consumer')) {
                $newUser->assignRole($consumerRole);
            }
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin']
        );

        $admin = Admin::updateOrCreate(
            ['email' => 'bilal.admin@meast.com'],
            [
                'name' => 'Bilal Admin',
                'password' => Hash::make('developer123'),
            ]
        );

        if (!$admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }
    }
}
