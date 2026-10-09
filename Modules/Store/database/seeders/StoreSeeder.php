<?php

namespace Modules\Store\Database\Seeders;

use Modules\Store\Models\Store;
use Modules\Core\Models\User;
use Modules\Store\Models\StoreMember;
use Modules\Store\Enums\StoreMemberRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the user with email bilal.mardini1999@gmail.com
        $user = User::where('email', 'bilal.mardini1999@gmail.com')->first();
        
        if (!$user) {
            $this->command->warn('User with email bilal.mardini1999@gmail.com not found. Please run UserSeeder first.');
            return;
        }
        
        $store = Store::updateOrCreate(
            ['slug' => 'bilals-store'],
            [
                'user_id' => $user->id,
                'name' => 'Bilal\'s Store',
                'country_code' => 'SY',
                'postal_code' => '',
                'region' => 'Damascus',
                'logo' => null,
                'description' => 'This is a sample store description.',
                'social_links' => [
                    'facebook' => 'https://facebook.com/bilalsstore',
                    'instagram' => 'https://instagram.com/bilalsstore'
                ],
                'phones' => ['1234567890', '0969616331'],
                'emails' => ['store@example.com'],
                'address' => '123 Main Street, City, Country',
                'is_returnable' => true,
                'rating' => 4.5,
            ]
        );

        // Create store owner member
        StoreMember::updateOrCreate(
            [
                'store_id' => $store->id,
                'user_id' => $user->id,
            ],
            [
                'role' => StoreMemberRole::STORE_OWNER,
                'status' => 'active',
                'email' => $user->email,
                'accepted_at' => now(),
            ]
        );
    }
}
