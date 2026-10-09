<?php

namespace Modules\Advertisement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Advertisement\Models\AdvertisementCategory;
use Modules\Advertisement\Models\AdvertisementSubcategory;

class AdvertisementSubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $electronics = AdvertisementCategory::where('slug', 'electronics')->first();
        $vehicles = AdvertisementCategory::where('slug', 'vehicles')->first();
        $furniture = AdvertisementCategory::where('slug', 'furniture')->first();
        $clothing = AdvertisementCategory::where('slug', 'clothing-accessories')->first();
        $homeGarden = AdvertisementCategory::where('slug', 'home-garden')->first();
        $sports = AdvertisementCategory::where('slug', 'sports-hobbies')->first();
        $books = AdvertisementCategory::where('slug', 'books-media')->first();
        $services = AdvertisementCategory::where('slug', 'services')->first();

        // Electronics Subcategories
        if ($electronics) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $electronics->id,
                'name' => ['en' => 'Smartphones', 'ar' => 'هواتف ذكية'],
                'slug' => Str::slug('Smartphones'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $electronics->id,
                'name' => ['en' => 'Laptops & Computers', 'ar' => 'أجهزة كمبيوتر محمولة'],
                'slug' => Str::slug('Laptops & Computers'),
                'sort_order' => 2,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $electronics->id,
                'name' => ['en' => 'Tablets', 'ar' => 'أجهزة لوحية'],
                'slug' => Str::slug('Tablets'),
                'sort_order' => 3,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $electronics->id,
                'name' => ['en' => 'TV & Audio', 'ar' => 'تلفزيون وصوتيات'],
                'slug' => Str::slug('TV & Audio'),
                'sort_order' => 4,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $electronics->id,
                'name' => ['en' => 'Cameras', 'ar' => 'كاميرات'],
                'slug' => Str::slug('Cameras'),
                'sort_order' => 5,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $electronics->id,
                'name' => ['en' => 'Gaming Consoles', 'ar' => 'أجهزة ألعاب'],
                'slug' => Str::slug('Gaming Consoles'),
                'sort_order' => 6,
                'is_active' => true,
            ]);
        }

        // Vehicles Subcategories
        if ($vehicles) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $vehicles->id,
                'name' => ['en' => 'Cars', 'ar' => 'سيارات'],
                'slug' => Str::slug('Cars'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $vehicles->id,
                'name' => ['en' => 'Motorcycles', 'ar' => 'دراجات نارية'],
                'slug' => Str::slug('Motorcycles'),
                'sort_order' => 2,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $vehicles->id,
                'name' => ['en' => 'Bicycles', 'ar' => 'دراجات هوائية'],
                'slug' => Str::slug('Bicycles'),
                'sort_order' => 3,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $vehicles->id,
                'name' => ['en' => 'Parts & Accessories', 'ar' => 'قطع غيار وإكسسوارات'],
                'slug' => Str::slug('Parts & Accessories'),
                'sort_order' => 4,
                'is_active' => true,
            ]);
        }

        // Furniture Subcategories
        if ($furniture) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $furniture->id,
                'name' => ['en' => 'Sofas & Chairs', 'ar' => 'أرائك وكراسي'],
                'slug' => Str::slug('Sofas & Chairs'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $furniture->id,
                'name' => ['en' => 'Tables', 'ar' => 'طاولات'],
                'slug' => Str::slug('Tables'),
                'sort_order' => 2,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $furniture->id,
                'name' => ['en' => 'Beds & Mattresses', 'ar' => 'أسرة ومراتب'],
                'slug' => Str::slug('Beds & Mattresses'),
                'sort_order' => 3,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $furniture->id,
                'name' => ['en' => 'Cabinets & Storage', 'ar' => 'خزائن وتخزين'],
                'slug' => Str::slug('Cabinets & Storage'),
                'sort_order' => 4,
                'is_active' => true,
            ]);
        }

        // Clothing Subcategories
        if ($clothing) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $clothing->id,
                'name' => ['en' => 'Men\'s Clothing', 'ar' => 'ملابس رجالية'],
                'slug' => Str::slug('Mens Clothing'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $clothing->id,
                'name' => ['en' => 'Women\'s Clothing', 'ar' => 'ملابس نسائية'],
                'slug' => Str::slug('Womens Clothing'),
                'sort_order' => 2,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $clothing->id,
                'name' => ['en' => 'Shoes', 'ar' => 'أحذية'],
                'slug' => Str::slug('Shoes'),
                'sort_order' => 3,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $clothing->id,
                'name' => ['en' => 'Bags & Accessories', 'ar' => 'حقائب وإكسسوارات'],
                'slug' => Str::slug('Bags & Accessories'),
                'sort_order' => 4,
                'is_active' => true,
            ]);
        }

        // Home & Garden Subcategories
        if ($homeGarden) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $homeGarden->id,
                'name' => ['en' => 'Kitchen Appliances', 'ar' => 'أجهزة المطبخ'],
                'slug' => Str::slug('Kitchen Appliances'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $homeGarden->id,
                'name' => ['en' => 'Garden Tools', 'ar' => 'أدوات الحديقة'],
                'slug' => Str::slug('Garden Tools'),
                'sort_order' => 2,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $homeGarden->id,
                'name' => ['en' => 'Home Decor', 'ar' => 'ديكور المنزل'],
                'slug' => Str::slug('Home Decor'),
                'sort_order' => 3,
                'is_active' => true,
            ]);
        }

        // Sports Subcategories
        if ($sports) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $sports->id,
                'name' => ['en' => 'Fitness Equipment', 'ar' => 'معدات اللياقة'],
                'slug' => Str::slug('Fitness Equipment'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $sports->id,
                'name' => ['en' => 'Sports Gear', 'ar' => 'معدات رياضية'],
                'slug' => Str::slug('Sports Gear'),
                'sort_order' => 2,
                'is_active' => true,
            ]);
        }

        // Books Subcategories
        if ($books) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $books->id,
                'name' => ['en' => 'Books', 'ar' => 'كتب'],
                'slug' => Str::slug('Books'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $books->id,
                'name' => ['en' => 'Movies & Music', 'ar' => 'أفلام وموسيقى'],
                'slug' => Str::slug('Movies & Music'),
                'sort_order' => 2,
                'is_active' => true,
            ]);
        }

        // Services Subcategories
        if ($services) {
            AdvertisementSubcategory::create([
                'advertisement_category_id' => $services->id,
                'name' => ['en' => 'Home Services', 'ar' => 'خدمات منزلية'],
                'slug' => Str::slug('Home Services'),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            AdvertisementSubcategory::create([
                'advertisement_category_id' => $services->id,
                'name' => ['en' => 'Professional Services', 'ar' => 'خدمات مهنية'],
                'slug' => Str::slug('Professional Services'),
                'sort_order' => 2,
                'is_active' => true,
            ]);
        }
    }
}

