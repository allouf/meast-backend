<?php

namespace Modules\Advertisement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Advertisement\Models\AdvertisementCategory;

class AdvertisementCategorySeeder extends Seeder
{
    public function run(): void
    {
        AdvertisementCategory::create(['name' => ['en' => 'Electronics', 'ar' => 'إلكترونيات'], 'slug' => Str::slug('Electronics'), 'icon' => 'electronics', 'sort_order' => 1, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Vehicles', 'ar' => 'مركبات'], 'slug' => Str::slug('Vehicles'), 'icon' => 'vehicles', 'sort_order' => 2, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Furniture', 'ar' => 'أثاث'], 'slug' => Str::slug('Furniture'), 'icon' => 'furniture', 'sort_order' => 3, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Clothing & Accessories', 'ar' => 'ملابس وإكسسوارات'], 'slug' => Str::slug('Clothing & Accessories'), 'icon' => 'clothing', 'sort_order' => 4, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Home & Garden', 'ar' => 'المنزل والحديقة'], 'slug' => Str::slug('Home & Garden'), 'icon' => 'home-garden', 'sort_order' => 5, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Sports & Hobbies', 'ar' => 'رياضة وهوايات'], 'slug' => Str::slug('Sports & Hobbies'), 'icon' => 'sports', 'sort_order' => 6, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Books & Media', 'ar' => 'كتب ووسائط'], 'slug' => Str::slug('Books & Media'), 'icon' => 'books', 'sort_order' => 7, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Services', 'ar' => 'خدمات'], 'slug' => Str::slug('Services'), 'icon' => 'services', 'sort_order' => 8, 'is_active' => true]);
        AdvertisementCategory::create(['name' => ['en' => 'Other', 'ar' => 'أخرى'], 'slug' => Str::slug('Other'), 'icon' => 'other', 'sort_order' => 9, 'is_active' => true]);
    }
}
