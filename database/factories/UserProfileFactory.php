<?php

namespace Database\Factories;

use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'avatar' => fake()->optional(0.6)->imageUrl(200, 200, 'people'),
            'preferences' => [
                'theme' => fake()->randomElement(['light', 'dark', 'auto']),
                'font_size' => fake()->randomElement(['small', 'medium', 'large']),
                'font_family' => fake()->randomElement(['vazir', 'yekan', 'tahoma']),
                'reading_mode' => fake()->randomElement(['scroll', 'page']),
            ],
            'metadata' => [
                'bio' => fake()->optional(0.4)->sentence(10),
                'location' => fake()->optional(0.5)->city(),
            ],
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}






