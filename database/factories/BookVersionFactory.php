<?php

namespace Database\Factories;

use App\Models\BookVersion;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookVersionFactory extends Factory
{
    protected $model = BookVersion::class;

    public function definition(): array
    {
        $format = fake()->randomElement(['epub', 'pdf', 'audio']);
        $size = match($format) {
            'epub' => fake()->numberBetween(1000000, 10000000),
            'pdf' => fake()->numberBetween(5000000, 50000000),
            'audio' => fake()->numberBetween(50000000, 500000000),
        };
        
        return [
            'book_id' => Book::inRandomOrder()->first()?->id ?? Book::factory(),
            'version' => '1.0',
            'format' => $format,
            'path' => "books/files/{$format}/" . fake()->uuid() . '.' . $format,
            'size' => $size,
            'duration_seconds' => $format === 'audio' ? fake()->numberBetween(3600, 36000) : null,
            'is_active' => true,
            'metadata' => [
                'quality' => fake()->randomElement(['standard', 'high', 'premium']),
                'bitrate' => $format === 'audio' ? '128kbps' : null,
            ],
        ];
    }

    public function epub(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'epub',
            'path' => 'books/files/epub/' . fake()->uuid() . '.epub',
            'size' => fake()->numberBetween(1000000, 10000000),
            'duration_seconds' => null,
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'pdf',
            'path' => 'books/files/pdf/' . fake()->uuid() . '.pdf',
            'size' => fake()->numberBetween(5000000, 50000000),
            'duration_seconds' => null,
        ]);
    }

    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'audio',
            'path' => 'books/files/audio/' . fake()->uuid() . '.mp3',
            'size' => fake()->numberBetween(50000000, 500000000),
            'duration_seconds' => fake()->numberBetween(3600, 36000),
        ]);
    }

    public function forBook(int $bookId): static
    {
        return $this->state(fn (array $attributes) => [
            'book_id' => $bookId,
        ]);
    }
}






