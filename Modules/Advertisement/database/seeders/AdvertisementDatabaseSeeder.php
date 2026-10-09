<?php

namespace Modules\Advertisement\Database\Seeders;

use Illuminate\Database\Seeder;

class AdvertisementDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdvertisementCategorySeeder::class,
            AdvertisementSubcategorySeeder::class,
            AdvertisementSeeder::class,
        ]);
    }
}
