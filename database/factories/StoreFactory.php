<?php

namespace Database\Factories;

use Modules\Store\Enums\StoreMemberRole;
use App\Models\Category;
use App\Models\Store;
use Modules\Core\Models\User;
use App\Models\StoreMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'user_id' => User::factory(), // owner will be linked later
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'logo' => null, // set in seeder or via getRandomImage()
            'cover' => null, // same here
            'description' => $this->faker->paragraph(),
            'social_links' => [
                'facebook' => $this->faker->url(),
                'instagram' => $this->faker->url(),
            ],
            'phones' => [$this->faker->phoneNumber()],
            'emails' => [$this->faker->safeEmail()],
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'website' => $this->faker->url(),
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'is_returnable' => $this->faker->boolean(80),
            'is_active' => $this->faker->boolean(90),
            'rating' => $this->faker->randomFloat(2, 3, 5),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Store $store) {
            $category = Category::inRandomOrder()->first();
            if ($category) {
                $store->categories()->attach($category);
            }

            // Create store owner member
            $user = $store->user;
            if ($user) {
                StoreMember::updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'user_id' => $store->user_id,
                    ],
                    [
                        'role' => StoreMemberRole::STORE_OWNER,
                        'status' => 'active',
                        'email' => $user->email,
                        'accepted_at' => now(),
                    ]
                );
            }

            $store->update([
                'logo' => $this->getRandomImageFromStorage('stores'),
                'cover' => $this->getRandomImageFromStorage('stores'),
            ]);
        });
    }

    protected function getRandomImageFromStorage(string $directory): ?string
    {
        $files = Storage::disk('public')->files($directory);

        if (empty($files)) {
            return null;
        }

        $file = $this->faker->randomElement($files);
        return 'storage/' . $file;
    }
}
