<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->optional(0.6)->sentence(10),
            'parent_id' => null,
            'image' => fake()->optional(0.4)->imageUrl(400, 300, 'categories'),
            'icon' => fake()->optional(0.5)->randomElement(['📚', '🔬', '🎨', '💻', '🏛️', '🌍']),
            'position' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(95),
            'type' => 'book',
        ];
    }

    public function withParent(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}






