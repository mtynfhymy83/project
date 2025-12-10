<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookContent;
use Illuminate\Database\Seeder;

class BookContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating book contents...');
        
        // برای 10 کتاب اول، محتوای کامل ایجاد کن
        $books = Book::published()->limit(10)->get();
        
        foreach ($books as $book) {
            $totalPages = rand(20, 50);
            
            for ($page = 1; $page <= $totalPages; $page++) {
                $paragraphsPerPage = rand(3, 8);
                
                // اگر صفحه اول یا هر 10 صفحه، یک index item بساز
                if ($page === 1 || $page % 10 === 0) {
                    BookContent::factory()
                        ->forBook($book->id)
                        ->page($page)
                        ->asIndex()
                        ->create([
                            'paragraph_number' => 0,
                            'order' => 0,
                        ]);
                }
                
                for ($para = 1; $para <= $paragraphsPerPage; $para++) {
                    $hasMedia = rand(1, 100) > 70; // 30% شانس داشتن رسانه
                    
                    $factory = BookContent::factory()
                        ->forBook($book->id)
                        ->page($page);
                    
                    if ($hasMedia && rand(1, 100) > 50) {
                        $factory = $factory->withAudio();
                    }
                    
                    if ($hasMedia && rand(1, 100) > 70) {
                        $factory = $factory->withImages();
                    }
                    
                    $factory->create([
                        'paragraph_number' => $para,
                        'order' => $para,
                    ]);
                }
            }
            
            $this->command->info("  ✓ Book '{$book->title}' - {$totalPages} pages created");
        }

        $this->command->info('✅ Book contents seeded successfully!');
    }
}






