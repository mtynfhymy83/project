<?php

namespace Database\Factories;

use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PublisherFactory extends Factory
{
    protected $model = Publisher::class;

    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->optional(0.6)->paragraph(2),
            'logo' => fake()->optional(0.5)->imageUrl(200, 200, 'business'),
            'website' => fake()->optional(0.7)->url(),
            'is_active' => fake()->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}








