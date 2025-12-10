<?php

namespace App\Observers;

use App\Models\Author;
use App\Jobs\SyncBookCache;

class AuthorObserver
{
    /**
     * Handle the Author "updated" event.
     */
    public function updated(Author $author): void
    {
        // اگر نام یا slug تغییر کرد، cache همه کتاب‌های مرتبط را sync کن
        if ($author->wasChanged(['name', 'slug'])) {
            $author->books()->each(function ($book) {
                SyncBookCache::dispatch($book->id);
            });
        }
    }

    /**
     * Handle the Author "deleted" event.
     */
    public function deleted(Author $author): void
    {
        // وقتی نویسنده حذف می‌شود، cache کتاب‌ها را sync کن
        $author->books()->each(function ($book) {
            SyncBookCache::dispatch($book->id);
        });
    }
}






