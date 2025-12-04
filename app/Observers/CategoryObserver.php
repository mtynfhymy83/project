<?php

namespace App\Observers;

use App\Models\Category;
use App\Jobs\SyncBookCache;

class CategoryObserver
{
    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        // اگر نام یا slug تغییر کرد، cache همه کتاب‌های مرتبط را sync کن
        if ($category->wasChanged(['name', 'slug'])) {
            $category->books()->each(function ($book) {
                SyncBookCache::dispatch($book->id);
            });
        }
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        // وقتی دسته‌بندی حذف می‌شود، cache کتاب‌ها را sync کن
        $category->books()->each(function ($book) {
            SyncBookCache::dispatch($book->id);
        });
    }
}

