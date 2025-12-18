<?php

namespace Database\Factories;

use App\Models\BookContent;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookContentFactory extends Factory
{
    protected $model = BookContent::class;

    public function definition(): array
    {
        $hasMedia = fake()->boolean(30);
        
        return [
            'book_id' => Book::inRandomOrder()->first()?->id ?? Book::factory(),
            'page_number' => fake()->numberBetween(1, 500),
            'paragraph_number' => fake()->numberBetween(1, 20),
            'order' => fake()->numberBetween(0, 50),
            'text' => fake()->paragraphs(rand(2, 5), true),
            'description' => fake()->optional(0.3)->sentence(10),
            'sound_path' => $hasMedia && fake()->boolean(50) 
                ? 'books/contents/audio/' . fake()->uuid() . '.mp3' 
                : null,
            'image_paths' => $hasMedia && fake()->boolean(40)
                ? json_encode([
                    'books/contents/images/' . fake()->uuid() . '.jpg',
                    'books/contents/images/' . fake()->uuid() . '.jpg',
                ])
                : null,
            'video_path' => $hasMedia && fake()->boolean(20)
                ? 'books/contents/videos/' . fake()->uuid() . '.mp4'
                : null,
            'is_index' => fake()->boolean(10),
            'index_title' => fake()->boolean(10) ? fake()->sentence(3) : null,
            'index_level' => fake()->numberBetween(0, 3),
        ];
    }

    public function forBook(int $bookId): static
    {
        return $this->state(fn (array $attributes) => [
            'book_id' => $bookId,
        ]);
    }

    public function page(int $pageNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'page_number' => $pageNumber,
        ]);
    }

    public function withAudio(): static
    {
        return $this->state(fn (array $attributes) => [
            'sound_path' => 'books/contents/audio/' . fake()->uuid() . '.mp3',
        ]);
    }

    public function withImages(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_paths' => json_encode([
                'books/contents/images/' . fake()->uuid() . '.jpg',
            ]),
        ]);
    }

    public function asIndex(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_index' => true,
            'index_title' => fake()->sentence(3),
            'index_level' => fake()->numberBetween(1, 3),
        ]);
    }
}








