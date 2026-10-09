<?php

namespace Database\Factories;

use Modules\Store\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductGallery;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use App\Models\ProductVariantOptionValue;
use App\Models\Store;
use App\Models\Category;
use App\Utilities\FileManager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    protected static array $realProductNames = [
        // Electronics & Phones
        'iPhone 15 Pro Max',
        'Samsung Galaxy S24 Ultra',
        'Google Pixel 8 Pro',
        'OnePlus 12',
        'MacBook Pro 16-inch',
        'Dell XPS 15',
        'HP Spectre x360',
        'Lenovo ThinkPad X1',
        'iPad Pro 12.9-inch',
        'Samsung Galaxy Tab S9',
        'Microsoft Surface Pro 9',
        'AirPods Pro 2',
        'Sony WH-1000XM5',
        'Bose QuietComfort 45',
        'JBL Flip 6',
        'Apple Watch Series 9',
        'Samsung Galaxy Watch 6',
        'Fitbit Charge 6',
        'Nintendo Switch OLED',
        'PlayStation 5',
        'Xbox Series X',

        // Clothing - Men
        'Classic White Dress Shirt',
        'Slim Fit Chinos',
        'Leather Jacket',
        'Denim Jeans',
        'Cotton T-Shirt Pack',
        'Wool Blazer',
        'Cargo Pants',
        'Polo Shirt',
        'Hooded Sweatshirt',
        'Running Shorts',
        'Formal Suit',
        'Winter Coat',

        // Clothing - Women
        'Floral Summer Dress',
        'Silk Blouse',
        'Skinny Jeans',
        'Maxi Dress',
        'Leather Handbag',
        'High Heel Shoes',
        'Ankle Boots',
        'Trench Coat',
        'Knit Sweater',
        'Pencil Skirt',
        'Blazer Jacket',
        'Evening Gown',

        // Beauty & Skincare
        'Vitamin C Serum',
        'Hyaluronic Acid Moisturizer',
        'Retinol Night Cream',
        'Sunscreen SPF 50',
        'Face Cleanser',
        'Eye Cream',
        'Face Mask Set',
        'Lipstick Set',
        'Mascara',
        'Foundation',
        'Concealer',
        'Blush Palette',
        'Shampoo & Conditioner Set',
        'Hair Mask',
        'Hair Oil',
        'Dry Shampoo',

        // Home & Kitchen
        'Stainless Steel Cookware Set',
        'Non-Stick Frying Pan',
        'Kitchen Knife Set',
        'Coffee Maker',
        'Blender',
        'Air Fryer',
        'Rice Cooker',
        'Toaster',
        'Dinnerware Set',
        'Glass Storage Containers',
        'Cutting Board',

        // Accessories
        'Leather Wallet',
        'Sunglasses',
        'Wristwatch',
        'Backpack',
        'Laptop Bag',
        'Phone Case',
        'Wireless Charger',
        'USB-C Cable',
        'Power Bank',
        'Car Phone Mount',
        'Bluetooth Speaker',

        // Sports & Fitness
        'Yoga Mat',
        'Dumbbell Set',
        'Resistance Bands',
        'Running Shoes',
        'Gym Bag',
        'Water Bottle',
        'Fitness Tracker',
        'Jump Rope',

        // Books & Media
        'Best Seller Novel',
        'Cookbook',
        'Self-Help Book',
        'Biography',
        'Children Book Set',
        'Mystery Thriller',
        'Science Fiction',

        // Toys & Games
        'LEGO Set',
        'Board Game',
        'Puzzle Set',
        'Action Figure',
        'Remote Control Car',
        'Building Blocks',
        'Art Supplies',

        // Health & Wellness
        'Multivitamin Supplements',
        'Protein Powder',
        'Yoga Block',
        'Foam Roller',
        'Massage Ball',
        'Essential Oil Diffuser',

        // Food & Beverages
        'Organic Coffee Beans',
        'Green Tea Collection',
        'Honey Jar',
        'Olive Oil',
        'Spice Set',
        'Gourmet Chocolate Box',

        // Pet Supplies
        'Dog Food',
        'Cat Litter',
        'Pet Toys',
        'Pet Bed',
        'Dog Leash',
        'Cat Scratching Post',

        // Automotive
        'Car Phone Holder',
        'Car Charger',
        'Dashboard Camera',
        'Car Air Freshener',
        'Tire Pressure Gauge',

        // Office Supplies
        'Notebook Set',
        'Pen Set',
        'Desk Organizer',
        'File Folder',
        'Stapler',
        'Paper Clips',
        'Sticky Notes',
    ];

    public function definition(): array
    {
        $name = $this->faker->randomElement(self::$realProductNames);
        $price = $this->faker->randomFloat(2, 10, 1000);
        $hasDiscount = $this->faker->boolean(30);
        $discountType = $hasDiscount ? $this->faker->randomElement(['percentage', 'fixed']) : null;
        $discountValue = $hasDiscount
            ? ($discountType === 'percentage'
                ? $this->faker->numberBetween(5, 50)
                : $this->faker->randomFloat(2, 5, $price * 0.5))
            : null;

        $finalPrice = $price;
        if ($hasDiscount && $discountValue) {
            if ($discountType === 'percentage') {
                $finalPrice = $price - ($price * ($discountValue / 100));
            } else {
                $finalPrice = $price - $discountValue;
            }
            $finalPrice = max($finalPrice, 0);
        }

        return [
            'store_id' => Store::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => $this->faker->paragraphs(3, true),
            'price' => $finalPrice,
            'price_before_discount' => $hasDiscount ? $price : null,
            'discount_value' => $discountValue,
            'discount_type' => $discountType,
            'number_of_buys' => $this->faker->numberBetween(0, 1000),
            'avg_rating' => $this->faker->randomFloat(1, 0, 5),
            'status' => $this->faker->randomElement([
                ProductStatus::ACTIVE,
                ProductStatus::DRAFT,
                ProductStatus::INACTIVE,
            ]),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            // Use images from categories folder
            $galleryImages = $this->getImagesFromCategories($product);

            if (!empty($galleryImages)) {
                $galleryData = [];
                $sortOrder = 0;

                foreach ($galleryImages as $imagePath) {
                    $galleryData[] = [
                        'product_id' => $product->id,
                        'file_path' => $imagePath,
                        'file_type' => FileManager::getFileTypeFromPath($imagePath),
                        'sort_order' => $sortOrder++,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($galleryData)) {
                    ProductGallery::insert($galleryData);
                }
            }

            // Create options and variants (50% chance)
            if ($this->faker->boolean(50)) {
                $this->createOptionsAndVariants($product);
            }
        });
    }

    protected function createOptionsAndVariants(Product $product): void
    {
        // Common option sets
        $optionSets = [
            [
                'name' => 'Color',
                'values' => ['Red', 'Blue', 'Green', 'Black', 'White', 'Yellow', 'Purple', 'Orange'],
            ],
            [
                'name' => 'Size',
                'values' => ['Small', 'Medium', 'Large', 'X-Large', 'XX-Large'],
            ],
            [
                'name' => 'Material',
                'values' => ['Cotton', 'Polyester', 'Wool', 'Leather', 'Silk', 'Denim'],
            ],
        ];

        // Select 1-2 random options
        $selectedOptions = $this->faker->randomElements($optionSets, rand(1, 2));
        $valueMap = [];

        // Create options and their values
        foreach ($selectedOptions as $optionData) {
            $option = ProductVariantOption::create([
                'product_id' => $product->id,
                'name' => $optionData['name'],
            ]);

            // Select 2-4 random values for this option
            $selectedValues = $this->faker->randomElements($optionData['values'], rand(2, min(4, count($optionData['values']))));

            foreach ($selectedValues as $value) {
                $optionValue = ProductVariantOptionValue::create([
                    'option_id' => $option->id,
                    'value' => $value,
                ]);
                $valueMap[$optionData['name']][$value] = $optionValue->id;
            }
        }

        // Generate all combinations of option values to create variants
        $combinations = $this->generateCombinations($valueMap);

        // Create variants (limit to max 10 variants)
        $maxVariants = min(10, count($combinations));
        $selectedCombinations = $this->faker->randomElements($combinations, $maxVariants);

        foreach ($selectedCombinations as $combination) {
            $variantPrice = $product->price;
            // 30% chance variant has different price
            if ($this->faker->boolean(30)) {
                $priceDiff = $this->faker->randomFloat(2, -50, 50);
                $variantPrice = max(0.01, $product->price + $priceDiff);
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => null, // Will be auto-generated by observer
                'price' => $variantPrice,
                'stock' => $this->faker->numberBetween(0, 100),
                'low_stock_threshold' => $this->faker->numberBetween(5, 20),
                'image' => $this->getRandomVariantImage(),
                'is_active' => $this->faker->boolean(90),
            ]);

            // Attach option values to variant
            $optionValueIds = [];
            foreach ($combination as $optionName => $value) {
                if (isset($valueMap[$optionName][$value])) {
                    $optionValueIds[] = $valueMap[$optionName][$value];
                }
            }

            if (!empty($optionValueIds)) {
                $variant->optionValues()->sync($optionValueIds);
                // Trigger SKU generation
                app(\App\Observers\ProductVariantObserver::class)
                    ->generateSkuAfterOptionValuesAttached($variant->fresh());
            }
        }
    }

    protected function generateCombinations(array $valueMap): array
    {
        if (empty($valueMap)) {
            return [];
        }

        $keys = array_keys($valueMap);
        $values = array_values($valueMap);

        // Generate all combinations using cartesian product
        $combinations = [[]];
        foreach ($values as $index => $optionValues) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach (array_keys($optionValues) as $value) {
                    $newCombination = $combination;
                    $newCombination[$keys[$index]] = $value;
                    $newCombinations[] = $newCombination;
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    protected function getRandomVariantImage(): ?string
    {
        // Use images from categories folder
        $files = Storage::disk('public')->files('categories');

        if (empty($files)) {
            return null;
        }

        // Filter only image files
        $imageFiles = array_filter($files, function ($file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']);
        });

        if (empty($imageFiles)) {
            return null;
        }

        $file = $this->faker->randomElement($imageFiles);
        return 'storage/' . $file;
    }

    protected function getImagesFromCategories(Product $product): array
    {
        $allFiles = Storage::disk('public')->files('categories');

        if (empty($allFiles)) {
            return [];
        }

        // Filter only image files
        $imageFiles = array_filter($allFiles, function ($file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']);
        });

        if (empty($imageFiles)) {
            return [];
        }

        $selectedImages = [];

        // First, try to match with category name if product has a category
        if ($product->category && $product->category->name) {
            $categoryName = $product->category->name;
            if (is_array($categoryName)) {
                $categoryName = $categoryName['en'] ?? $categoryName['ar'] ?? '';
            }
            $categorySlug = Str::slug($categoryName);

            foreach ($imageFiles as $file) {
                $fileName = strtolower(pathinfo($file, PATHINFO_FILENAME));
                $fileNameSlug = Str::slug($fileName);

                // Check if category name matches the filename
                if ($fileNameSlug === $categorySlug || str_contains($fileNameSlug, $categorySlug) || str_contains($categorySlug, $fileNameSlug)) {
                    $imagePath = 'storage/' . $file;
                    if (!in_array($imagePath, $selectedImages)) {
                        $selectedImages[] = $imagePath;
                    }
                }
            }
        }

        // If we found category matches, use them (1-3 images)
        if (!empty($selectedImages)) {
            $count = min(rand(1, 3), count($selectedImages));
            return $this->faker->randomElements($selectedImages, $count);
        }

        // Otherwise, use random images from categories folder (1-5 images)
        $count = rand(1, min(5, count($imageFiles)));
        $selectedFiles = $this->faker->randomElements($imageFiles, $count);

        return array_map(function ($file) {
            return 'storage/' . $file;
        }, $selectedFiles);
    }

    protected function getRandomImagesFromStorage(string $directory, int $count = 1): array
    {
        $files = Storage::disk('public')->files($directory);

        if (empty($files)) {
            return [];
        }

        // Filter only image files
        $imageFiles = array_filter($files, function ($file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']);
        });

        if (empty($imageFiles)) {
            return [];
        }

        $selectedFiles = $this->faker->randomElements($imageFiles, min($count, count($imageFiles)));

        return array_map(function ($file) {
            return 'storage/' . $file;
        }, $selectedFiles);
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProductStatus::ACTIVE,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProductStatus::DRAFT,
        ]);
    }

    public function withDiscount(string $type = 'percentage', ?float $value = null): static
    {
        return $this->state(function (array $attributes) use ($type, $value) {
            $price = $attributes['price'] ?? $this->faker->randomFloat(2, 10, 1000);
            $discountValue = $value ?? ($type === 'percentage'
                ? $this->faker->numberBetween(5, 50)
                : $this->faker->randomFloat(2, 5, $price * 0.5));

            $finalPrice = $price;
            if ($type === 'percentage') {
                $finalPrice = $price - ($price * ($discountValue / 100));
            } else {
                $finalPrice = $price - $discountValue;
            }
            $finalPrice = max($finalPrice, 0);

            return [
                'price' => $finalPrice,
                'price_before_discount' => $price,
                'discount_value' => $discountValue,
                'discount_type' => $type,
            ];
        });
    }
}
