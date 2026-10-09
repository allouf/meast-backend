<?php

namespace Modules\Job\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Job\Models\JobCategory;

class JobCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'IT', 'description' => 'Information Technology and Software', 'sort_order' => 1],
            ['name' => 'Healthcare', 'description' => 'Medical and Health Services', 'sort_order' => 2],
            ['name' => 'Education', 'description' => 'Teaching and Educational Services', 'sort_order' => 3],
            ['name' => 'Marketing', 'description' => 'Marketing and Communications', 'sort_order' => 4],
            ['name' => 'Finance', 'description' => 'Finance and Accounting', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            JobCategory::create(array_merge($category, ['is_active' => true]));
        }
    }
}
