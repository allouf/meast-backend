<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\Core\Database\Seeders\CoreDatabaseSeeder::class,
            \Modules\Store\Database\Seeders\StoreDatabaseSeeder::class,
            \Modules\Job\Database\Seeders\LanguageSeeder::class,
            // \Modules\Advertisement\Database\Seeders\AdvertisementDatabaseSeeder::class,
        ]);
    }
}
