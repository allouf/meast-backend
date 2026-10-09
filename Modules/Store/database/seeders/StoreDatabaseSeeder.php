<?php

namespace Modules\Store\Database\Seeders;

use Illuminate\Database\Seeder;

class StoreDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IssueTypeSeeder::class,
            ViolationReasonSeeder::class,
            // CategoriesTableSeeder::class,
            // StoreSeeder::class,
            // StoreFactorySeeder::class,
            // CategoryFactorySeeder::class,
            // ProductSeeder::class,
        ]);
    }
}
