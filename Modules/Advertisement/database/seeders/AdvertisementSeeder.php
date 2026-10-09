<?php

namespace Modules\Advertisement\Database\Seeders;

use Modules\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Advertisement\Enums\AdvertisementStatus;
use Modules\Advertisement\Models\Advertisement;
use Modules\Advertisement\Models\AdvertisementCategory;
use Modules\Advertisement\Models\AdvertisementSubcategory;

class AdvertisementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users, categories, and locations
        $users = User::take(5)->get();
        $electronics = AdvertisementCategory::where('slug', 'electronics')->first();
        $vehicles = AdvertisementCategory::where('slug', 'vehicles')->first();
        $furniture = AdvertisementCategory::where('slug', 'furniture')->first();
        $clothing = AdvertisementCategory::where('slug', 'clothing-accessories')->first();

        $smartphones = AdvertisementSubcategory::where('slug', 'smartphones')->first();
        $laptops = AdvertisementSubcategory::where('slug', 'laptops-computers')->first();
        $cars = AdvertisementSubcategory::where('slug', 'cars')->first();

        if ($users->isEmpty() || !$electronics) {
            $this->command->warn('Please run UserSeeder and AdvertisementCategorySeeder first!');
            return;
        }

        $countryCode = 'SY';
        $regionName = 'Damascus';

        // Sample advertisements
        $advertisements = [
            // Electronics
            [
                'user_id' => $users->random()->id,
                'advertisement_category_id' => $electronics->id,
                'advertisement_subcategory_id' => $smartphones?->id,
                'country_code' => $countryCode,
                'region' => $regionName,
                'title' => 'iPhone 13 Pro Max 256GB - Excellent Condition',
                'slug' => Str::slug('iPhone 13 Pro Max 256GB') . '-' . time(),
                'description' => 'iPhone 13 Pro Max in excellent condition. Used for 6 months, comes with original box, charger, and screen protector. No scratches or damages. Battery health 98%.',
                'price' => 850.00,
                'is_price_negotiable' => true,
                'is_free' => false,
                'condition' => 'used',
                'status' => AdvertisementStatus::ACTIVE->value,
                'contact_method' => 'both',
                'phone' => '+963991234567',
                'show_phone' => false,
                'images' => null,
                'views_count' => rand(10, 500),
                'published_at' => now()->subDays(rand(1, 10)),
                'expires_at' => now()->addDays(rand(20, 30)),
                'attributes' => [
                    'brand' => 'Apple',
                    'model' => 'iPhone 13 Pro Max',
                    'storage' => '256GB',
                    'color' => 'Graphite',
                    'battery_health' => '98%'
                ],
            ],
            [
                'user_id' => $users->random()->id,
                'advertisement_category_id' => $electronics->id,
                'advertisement_subcategory_id' => $laptops?->id,
                'country_code' => $countryCode,
                'region' => $regionName,
                'title' => 'MacBook Pro M1 13 inch - Like New',
                'slug' => Str::slug('MacBook Pro M1') . '-' . time(),
                'description' => 'MacBook Pro M1 chip, 13 inch display, 8GB RAM, 256GB SSD. Purchased 3 months ago, still under warranty. Perfect condition, barely used.',
                'price' => 1200.00,
                'is_price_negotiable' => false,
                'is_free' => false,
                'condition' => 'new',
                'status' => AdvertisementStatus::ACTIVE->value,
                'contact_method' => 'chat',
                'phone' => null,
                'show_phone' => false,
                'images' => null,
                'views_count' => rand(10, 500),
                'published_at' => now()->subDays(rand(1, 10)),
                'expires_at' => now()->addDays(rand(20, 30)),
                'attributes' => [
                    'brand' => 'Apple',
                    'model' => 'MacBook Pro',
                    'processor' => 'M1',
                    'ram' => '8GB',
                    'storage' => '256GB SSD',
                    'screen_size' => '13 inch'
                ],
            ],
            [
                'user_id' => $users->random()->id,
                'advertisement_category_id' => $electronics->id,
                'advertisement_subcategory_id' => $smartphones?->id,
                'country_code' => $countryCode,
                'region' => $regionName,
                'title' => 'Samsung Galaxy S21 Ultra - Good Condition',
                'slug' => Str::slug('Samsung Galaxy S21 Ultra') . '-' . time(),
                'description' => 'Samsung Galaxy S21 Ultra, 128GB. Good condition with minor scratches on screen. Works perfectly. Comes with charger.',
                'price' => 600.00,
                'is_price_negotiable' => true,
                'is_free' => false,
                'condition' => 'used',
                'status' => AdvertisementStatus::ACTIVE->value,
                'contact_method' => 'phone',
                'phone' => '+963992345678',
                'show_phone' => true,
                'images' => null,
                'views_count' => rand(10, 500),
                'published_at' => now()->subDays(rand(1, 10)),
                'expires_at' => now()->addDays(rand(20, 30)),
                'attributes' => [
                    'brand' => 'Samsung',
                    'model' => 'Galaxy S21 Ultra',
                    'storage' => '128GB',
                    'color' => 'Phantom Black'
                ],
            ],
            // Vehicles
            [
                'user_id' => $users->random()->id,
                'advertisement_category_id' => $vehicles->id,
                'advertisement_subcategory_id' => $cars?->id,
                'country_code' => $countryCode,
                'region' => $regionName,
                'title' => 'Toyota Camry 2018 - Well Maintained',
                'slug' => Str::slug('Toyota Camry 2018') . '-' . time(),
                'description' => 'Toyota Camry 2018, excellent condition, regularly serviced. Low mileage, all service records available. No accidents.',
                'price' => 15000.00,
                'is_price_negotiable' => true,
                'is_free' => false,
                'condition' => 'used',
                'status' => AdvertisementStatus::ACTIVE->value,
                'contact_method' => 'both',
                'phone' => '+963993456789',
                'show_phone' => false,
                'images' => null,
                'views_count' => rand(50, 1000),
                'published_at' => now()->subDays(rand(1, 10)),
                'expires_at' => now()->addDays(rand(20, 30)),
                'attributes' => [
                    'brand' => 'Toyota',
                    'model' => 'Camry',
                    'year' => '2018',
                    'mileage' => '45000 km',
                    'fuel_type' => 'Gasoline',
                    'transmission' => 'Automatic'
                ],
            ],
            // Furniture
            [
                'user_id' => $users->random()->id,
                'advertisement_category_id' => $furniture->id,
                'advertisement_subcategory_id' => null,
                'country_code' => $countryCode,
                'region' => $regionName,
                'title' => 'Modern Sofa Set - 3 Seater',
                'slug' => Str::slug('Modern Sofa Set') . '-' . time(),
                'description' => 'Beautiful modern sofa set, 3 seater in excellent condition. Light gray color, very comfortable. Moving sale.',
                'price' => 350.00,
                'is_price_negotiable' => true,
                'is_free' => false,
                'condition' => 'used',
                'status' => AdvertisementStatus::ACTIVE->value,
                'contact_method' => 'both',
                'phone' => '+963994567890',
                'show_phone' => false,
                'images' => null,
                'views_count' => rand(10, 200),
                'published_at' => now()->subDays(rand(1, 10)),
                'expires_at' => now()->addDays(rand(20, 30)),
                'attributes' => [
                    'type' => 'Sofa',
                    'seats' => '3',
                    'color' => 'Light Gray',
                    'material' => 'Fabric'
                ],
            ],
            // Clothing
            [
                'user_id' => $users->random()->id,
                'advertisement_category_id' => $clothing->id,
                'advertisement_subcategory_id' => null,
                'country_code' => $countryCode,
                'region' => $regionName,
                'title' => 'Designer Handbag - Brand New',
                'slug' => Str::slug('Designer Handbag') . '-' . time(),
                'description' => 'Brand new designer handbag, never used. Still has tags. Perfect gift item.',
                'price' => 150.00,
                'is_price_negotiable' => false,
                'is_free' => false,
                'condition' => 'new',
                'status' => AdvertisementStatus::PAUSED->value,
                'contact_method' => 'chat',
                'phone' => null,
                'show_phone' => false,
                'images' => null,
                'views_count' => rand(5, 100),
                'published_at' => now()->subDays(rand(1, 10)),
                'expires_at' => now()->addDays(rand(20, 30)),
                'attributes' => [
                    'brand' => 'Designer',
                    'type' => 'Handbag',
                    'color' => 'Black',
                    'material' => 'Leather'
                ],
            ],
            // Draft advertisement
            [
                'user_id' => $users->random()->id,
                'advertisement_category_id' => $electronics->id,
                'advertisement_subcategory_id' => $smartphones?->id,
                'country_code' => $countryCode,
                'region' => $regionName,
                'title' => 'Samsung Galaxy Note 20',
                'slug' => Str::slug('Samsung Galaxy Note 20') . '-' . time(),
                'description' => 'Samsung Galaxy Note 20 in good condition.',
                'price' => 500.00,
                'is_price_negotiable' => true,
                'is_free' => false,
                'condition' => 'used',
                'status' => AdvertisementStatus::DRAFT->value,
                'contact_method' => 'both',
                'phone' => '+963995678901',
                'show_phone' => false,
                'images' => null,
                'views_count' => 0,
                'published_at' => null,
                'expires_at' => null,
                'attributes' => null,
            ],
        ];

        foreach ($advertisements as $adData) {
            Advertisement::create($adData);
        }

        $this->command->info('Created ' . count($advertisements) . ' sample advertisements!');
    }
}

