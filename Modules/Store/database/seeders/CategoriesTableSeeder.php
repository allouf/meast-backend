<?php

namespace Modules\Store\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Store\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategoriesTableSeeder extends Seeder
{
    protected function getIconForCategory(string $categoryName): ?string
    {
        // Map category names to image files in storage
        $iconMap = [
            'Electronic Devices' => 'electronics.jpeg',
            'Smartphones' => 'mobile.png',
            'Laptops' => 'laptop.png',
            'Accessories' => 'accessories.png',
            'Chargers' => 'chargers.jpg',
            'Clothing' => 'clothes.webp',
            'Men' => 'men.jpg',
            'Women' => 'women.webp',
            'Kids' => 'kids.webp',
            'Shirts' => 'shirt.webp',
            'Pants' => 'pants.webp',
            'Dresses' => 'dress.webp',
            'Skirts' => 'skirts.webp',
            'Boys' => 'boy.webp',
            'Girls' => 'girls.webp',
            'Beauty Care' => 'beauty.png',
            'Skincare' => 'skincare.webp',
            'Makeup' => 'makeup.webp',
            'Hair Care' => 'hair-care.webp',
            'Cleansers' => 'cleansers.webp',
            'Moisturizers' => 'moisturizers.webp',
            'Lipsticks' => 'lipsticks.webp',
            'Eyeshadow' => 'Eyeshadow.webp',
            'Shampoos' => 'Shampoos.webp',
            'Conditioners' => 'Conditioners.webp',
        ];

        $iconFile = $iconMap[$categoryName] ?? null;

        if ($iconFile && Storage::disk('public')->exists('categories/' . $iconFile)) {
            return 'storage/categories/' . $iconFile;
        }

        return null;
    }

    public function run(): void
    {
        // ===== Electronics =====
        $electronics = Category::create([
            'name' => ['en' => 'Electronic Devices', 'ar' => 'الأجهزة الإلكترونية'],
            'icon' => $this->getIconForCategory('Electronic Devices'),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $phones = Category::create([
            'name' => ['en' => 'Smartphones', 'ar' => 'الهواتف الذكية'],
            'icon' => $this->getIconForCategory('Smartphones'),
            'parent_id' => $electronics->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $laptops = Category::create([
            'name' => ['en' => 'Laptops', 'ar' => 'الحواسيب المحمولة'],
            'icon' => $this->getIconForCategory('Laptops'),
            'parent_id' => $electronics->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $accessories = Category::create([
            'name' => ['en' => 'Accessories', 'ar' => 'الإكسسوارات'],
            'icon' => $this->getIconForCategory('Accessories'),
            'parent_id' => $electronics->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        Category::create([
            'name' => ['en' => 'Chargers', 'ar' => 'الشواحن'],
            'icon' => $this->getIconForCategory('Chargers'),
            'parent_id' => $accessories->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // ===== Clothing =====
        $clothes = Category::create([
            'name' => ['en' => 'Clothing', 'ar' => 'الملابس'],
            'icon' => $this->getIconForCategory('Clothing'),
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $men = Category::create([
            'name' => ['en' => 'Men', 'ar' => 'رجالي'],
            'icon' => $this->getIconForCategory('Men'),
            'parent_id' => $clothes->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $women = Category::create([
            'name' => ['en' => 'Women', 'ar' => 'نسائي'],
            'icon' => $this->getIconForCategory('Women'),
            'parent_id' => $clothes->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $kids = Category::create([
            'name' => ['en' => 'Kids', 'ar' => 'أطفال'],
            'icon' => $this->getIconForCategory('Kids'),
            'parent_id' => $clothes->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Subcategories for Men
        Category::create([
            'name' => ['en' => 'Shirts', 'ar' => 'قمصان'],
            'icon' => $this->getIconForCategory('Shirts'),
            'parent_id' => $men->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => ['en' => 'Pants', 'ar' => 'بنطلونات'],
            'icon' => $this->getIconForCategory('Pants'),
            'parent_id' => $men->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Subcategories for Women
        Category::create([
            'name' => ['en' => 'Dresses', 'ar' => 'فساتين'],
            'icon' => $this->getIconForCategory('Dresses'),
            'parent_id' => $women->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => ['en' => 'Skirts', 'ar' => 'تنانير'],
            'icon' => $this->getIconForCategory('Skirts'),
            'parent_id' => $women->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Subcategories for Kids
        Category::create([
            'name' => ['en' => 'Boys', 'ar' => 'أولاد'],
            'icon' => $this->getIconForCategory('Boys'),
            'parent_id' => $kids->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => ['en' => 'Girls', 'ar' => 'بنات'],
            'icon' => $this->getIconForCategory('Girls'),
            'parent_id' => $kids->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // ===== Beauty Care =====
        $beautyCare = Category::create([
            'name' => ['en' => 'Beauty Care', 'ar' => 'العناية بالجمال'],
            'icon' => $this->getIconForCategory('Beauty Care'),
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $skincare = Category::create([
            'name' => ['en' => 'Skincare', 'ar' => 'العناية بالبشرة'],
            'icon' => $this->getIconForCategory('Skincare'),
            'parent_id' => $beautyCare->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $makeup = Category::create([
            'name' => ['en' => 'Makeup', 'ar' => 'المكياج'],
            'icon' => $this->getIconForCategory('Makeup'),
            'parent_id' => $beautyCare->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $hairCare = Category::create([
            'name' => ['en' => 'Hair Care', 'ar' => 'العناية بالشعر'],
            'icon' => $this->getIconForCategory('Hair Care'),
            'parent_id' => $beautyCare->id,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Subcategories for Skincare
        Category::create([
            'name' => ['en' => 'Cleansers', 'ar' => 'منظفات'],
            'icon' => $this->getIconForCategory('Cleansers'),
            'parent_id' => $skincare->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => ['en' => 'Moisturizers', 'ar' => 'مرطبات'],
            'icon' => $this->getIconForCategory('Moisturizers'),
            'parent_id' => $skincare->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Subcategories for Makeup
        Category::create([
            'name' => ['en' => 'Lipsticks', 'ar' => 'أحمر الشفاه'],
            'icon' => $this->getIconForCategory('Lipsticks'),
            'parent_id' => $makeup->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => ['en' => 'Eyeshadow', 'ar' => 'ظلال العيون'],
            'icon' => $this->getIconForCategory('Eyeshadow'),
            'parent_id' => $makeup->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Subcategories for Hair Care
        Category::create([
            'name' => ['en' => 'Shampoos', 'ar' => 'شامبو'],
            'icon' => $this->getIconForCategory('Shampoos'),
            'parent_id' => $hairCare->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => ['en' => 'Conditioners', 'ar' => 'بلسم الشعر'],
            'icon' => $this->getIconForCategory('Conditioners'),
            'parent_id' => $hairCare->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);
    }
}
