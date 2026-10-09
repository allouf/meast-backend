<?php

namespace Modules\Store\Database\Seeders;

use Modules\Store\Models\Category;
use Illuminate\Database\Seeder;

class CategoryFactorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create root categories using factory
        $rootCategoryCount = 5;
        
        $rootCategories = Category::factory($rootCategoryCount)->root()->create();
        
        // Create child categories for each root category
        foreach ($rootCategories as $rootCategory) {
            $childCount = rand(2, 4);
            Category::factory($childCount)
                ->childOf($rootCategory->id)
                ->create();
        }
        
        $this->command->info("Created {$rootCategoryCount} root categories and their children using factory!");
    }
}

