<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => [
                'en' => ucwords($name),
                'ar' => $this->faker->words(2, true) . ' (AR)',
            ],
            'icon' => $this->getRandomIconFromStorage(),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    protected function getRandomIconFromStorage(): ?string
    {
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

    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    public function childOf(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }
}

