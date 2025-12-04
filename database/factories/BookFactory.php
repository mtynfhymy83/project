<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Publisher;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 6));
        $price = fake()->randomFloat(2, 10000, 500000);
        $hasDiscount = fake()->boolean(30);
        
        return [
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 99999),
            'excerpt' => fake()->paragraph(2),
            'content' => fake()->paragraphs(5, true),
            'isbn' => fake()->optional(0.7)->isbn13(),
            'publisher_id' => Publisher::inRandomOrder()->first()?->id,
            'primary_category_id' => Category::inRandomOrder()->first()?->id,
            'cover_image' => 'books/covers/' . fake()->uuid() . '.jpg',
            'thumbnail' => 'books/thumbnails/' . fake()->uuid() . '.jpg',
            'icon' => fake()->optional(0.3)->randomElement(['📖', '📚', '📕', '📗', '📘']),
            'pages' => fake()->numberBetween(50, 1000),
            'file_size' => fake()->numberBetween(1000000, 50000000),
            'features' => json_encode([
                'has_audio' => fake()->boolean(40),
                'has_video' => fake()->boolean(20),
                'has_images' => fake()->boolean(60),
                'has_questions' => fake()->boolean(50),
                'has_download' => fake()->boolean(80),
            ]),
            'price' => $price,
            'discount_price' => $hasDiscount ? $price * 0.8 : null,
            'is_free' => fake()->boolean(15),
            'meta_keywords' => fake()->optional(0.5)->words(5, true),
            'meta_description' => fake()->optional(0.5)->sentence(15),
            'tags' => fake()->optional(0.6)->words(4, true),
            'status' => fake()->randomElement(['published', 'published', 'published', 'draft']),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free' => true,
            'price' => 0,
            'discount_price' => null,
        ]);
    }

    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? 100000;
            return [
                'discount_price' => $price * 0.7,
            ];
        });
    }
}

