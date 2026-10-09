<?php

namespace Modules\Store\Database\Seeders;

use Modules\Store\Models\Product;
use Modules\Store\Models\Store;
use Modules\Store\Models\Category;
use Modules\Store\Enums\ProductStatus;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing stores and categories
        $stores = Store::all();
        $categories = Category::all();

        if ($stores->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('No stores or categories found. Please run StoreSeeder and CategoriesTableSeeder first.');
            return;
        }

        $totalProducts = 200;
        $productsCreated = 0;

        // Create 200 products distributed across stores
        while ($productsCreated < $totalProducts) {
            foreach ($stores as $store) {
                if ($productsCreated >= $totalProducts) {
                    break;
                }

                // Get categories that belong to this store
                $storeCategories = $store->categories;

                if ($storeCategories->isEmpty()) {
                    // If store has no categories, use random categories
                    $storeCategories = $categories->random(min(3, $categories->count()));
                }

                // Calculate how many products to create for this store
                $remainingProducts = $totalProducts - $productsCreated;
                $productsPerStore = min(rand(10, 30), $remainingProducts);

                for ($i = 0; $i < $productsPerStore && $productsCreated < $totalProducts; $i++) {
                    $category = $storeCategories->random();

                    // Mix of active, draft, and discounted products
                    $factory = Product::factory()->state([
                        'store_id' => $store->id,
                        'category_id' => $category->id,
                    ]);

                    // 70% active, 20% draft, 10% with discounts
                    $rand = rand(1, 100);
                    if ($rand <= 70) {
                        $factory->active()->create();
                    } elseif ($rand <= 90) {
                        $factory->draft()->create();
                    } else {
                        $factory->active()
                            ->withDiscount(rand(0, 1) ? 'percentage' : 'fixed')
                            ->create();
                    }

                    $productsCreated++;
                }
            }
        }

        $this->command->info("Created {$productsCreated} products with real names successfully!");
    }
}
