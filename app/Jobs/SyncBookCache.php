<?php

namespace App\Jobs;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBookCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $bookId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $book = Book::find($this->bookId);
        
        if (!$book) {
            return;
        }

        // Sync authors cache
        $book->authors_cache = $book->authors()
            ->orderBy('book_author.order')
            ->get()
            ->map(fn($author) => [
                'id' => $author->id,
                'name' => $author->name,
                'slug' => $author->slug,
            ])
            ->toArray();

        // Sync categories cache
        $book->categories_cache = $book->categories()
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->toArray();

        // Save without triggering events
        $book->saveQuietly();
    }
}








