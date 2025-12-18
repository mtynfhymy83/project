<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\BookVersion;
use App\Models\BookStats;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating books...');
        
        // ایجاد 100 کتاب
        Book::factory()
            ->count(100)
            ->create()
            ->each(function ($book) {
                // اضافه کردن نویسندگان (1-3 نویسنده)
                $authorCount = rand(1, 3);
                $authors = Author::inRandomOrder()->limit($authorCount)->get();
                
                $authorsData = [];
                foreach ($authors as $index => $author) {
                    $authorsData[$author->id] = ['order' => $index];
                }
                $book->authors()->attach($authorsData);
                
                // اضافه کردن دسته‌بندی‌ها (1-3 دسته)
                $categoryCount = rand(1, 3);
                $categories = Category::inRandomOrder()->limit($categoryCount)->get();
                $book->categories()->attach($categories->pluck('id'));
                
                // Sync cache
                $book->syncAuthorsCache();
                $book->syncCategoriesCache();
                
                // ایجاد نسخه‌های مختلف (epub, pdf, audio)
                BookVersion::factory()->epub()->forBook($book->id)->create();
                
                if (rand(1, 100) > 30) {
                    BookVersion::factory()->pdf()->forBook($book->id)->create();
                }
                
                if (rand(1, 100) > 60) {
                    BookVersion::factory()->audio()->forBook($book->id)->create();
                }
                
                // ایجاد آمار (trigger خودکار ایجاد می‌کند، اما برای مطمئن شدن)
                if (!$book->stats) {
                    BookStats::create([
                        'book_id' => $book->id,
                        'view_count' => rand(0, 10000),
                        'purchase_count' => rand(0, 500),
                        'download_count' => rand(0, 1000),
                        'rating' => rand(30, 50) / 10,
                        'rating_count' => rand(0, 200),
                        'favorite_count' => rand(0, 300),
                        'updated_at' => now(),
                    ]);
                }
            });

        $this->command->info('✅ Books seeded successfully! (100 books with relations)');
    }
}








