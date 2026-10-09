<?php

namespace Modules\Store\Database\Seeders;

use Modules\Store\Models\Store;
use Modules\Core\Models\User;
use Illuminate\Database\Seeder;

class StoreFactorySeeder extends Seeder
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
        
        // Create stores using factory, all assigned to this user
        $storeCount = 10; // You can adjust this number
        
        Store::factory($storeCount)->create([
            'user_id' => $user->id,
        ]);
        
        $this->command->info("Created {$storeCount} stores using factory for user: {$user->email}!");
    }
}

