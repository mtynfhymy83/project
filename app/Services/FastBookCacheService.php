<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookDetailCache;
use App\Models\BookStats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FastBookCacheService
{
    // Cache TTL
    private const REDIS_TTL = 3600; // 1 hour in Redis
    private const DETAIL_CACHE_TTL = 86400; // 24 hours in database
    
    /**
     * دریافت جزئیات کتاب با 3-Layer Cache (فوق‌العاده سریع)
     * 
     * Performance:
     * - Redis hit: ~1ms
     * - DB cache hit: ~5-10ms  
     * - Full load: ~50ms (فقط اولین بار)
     */
    public function getBookDetail(int $bookId): ?array
    {
        $cacheKey = "book:ultra:detail:{$bookId}";
        
        // Layer 1: Redis (fastest ~1ms)
        $data = Cache::get($cacheKey);
        if ($data) {
            return array_merge(json_decode($data, true), ['source' => 'redis']);
        }
        
        // Layer 2: book_detail_cache table (~5-10ms)
        $cached = BookDetailCache::where('book_id', $bookId)
            ->where('updated_at', '>', now()->subSeconds(self::DETAIL_CACHE_TTL))
            ->first();
            
        if ($cached) {
            // Store in Redis for next time
            Cache::put($cacheKey, json_encode($cached->payload), self::REDIS_TTL);
            return array_merge($cached->payload, ['source' => 'db_cache']);
        }
        
        // Layer 3: Full database load (~50ms, فقط اولین بار)
        return $this->loadAndCacheFromDatabase($bookId);
    }
    
    /**
     * بارگذاری از دیتابیس و کش در همه لایه‌ها
     */
    private function loadAndCacheFromDatabase(int $bookId): ?array
    {
        $cacheKey = "book:ultra:detail:{$bookId}";
        
        // کوئری بهینه شده با استفاده از cache fields
        $book = DB::table('books')
            ->select([
                'books.id',
                'books.title',
                'books.slug',
                'books.excerpt',
                'books.content',
                'books.cover_image',
                'books.thumbnail',
                'books.pages',
                'books.file_size',
                'books.price',
                'books.discount_price',
                'books.is_free',
                'books.features',
                'books.authors_cache',      // از cache استفاده می‌کنیم!
                'books.categories_cache',   // از cache استفاده می‌کنیم!
                'books.status',
                'books.created_at',
                'p.id as publisher_id',
                'p.name as publisher_name',
                'c.id as primary_category_id',
                'c.name as primary_category_name',
                'c.slug as primary_category_slug',
            ])
            ->leftJoin('publishers as p', 'books.publisher_id', '=', 'p.id')
            ->leftJoin('categories as c', 'books.primary_category_id', '=', 'c.id')
            ->where('books.id', $bookId)
            ->where('books.status', 'published')
            ->first();
            
        if (!$book) {
            return null;
        }
        
        // دریافت آمار از جدول جدا (سریع)
        $stats = DB::table('book_stats')
            ->where('book_id', $bookId)
            ->first([
                'view_count',
                'purchase_count',
                'download_count',
                'rating',
                'rating_count',
                'favorite_count'
            ]);
        
        // دریافت فهرست کتاب (کوئری ساده و سریع)
        $index = DB::table('book_contents')
            ->where('book_id', $bookId)
            ->where('is_index', true)
            ->orderBy('page_number')
            ->orderBy('order')
            ->get(['id', 'index_title', 'index_level', 'page_number'])
            ->map(fn($item) => [
                'id' => $item->id,
                'title' => $item->index_title,
                'level' => $item->index_level,
                'page' => $item->page_number,
            ])
            ->toArray();
        
        // ساخت آرایه نهایی
        $data = [
            'id' => $book->id,
            'title' => $book->title,
            'slug' => $book->slug,
            'excerpt' => $book->excerpt,
            'content' => $book->content,
            'cover_url' => $book->cover_image ? asset('storage/' . $book->cover_image) : null,
            'thumbnail_url' => $book->thumbnail ? asset('storage/' . $book->thumbnail) : null,
            'pages' => $book->pages,
            'file_size' => $book->file_size,
            'price' => (float) $book->price,
            'discount_price' => $book->discount_price ? (float) $book->discount_price : null,
            'effective_price' => $book->discount_price ?? $book->price,
            'has_discount' => !is_null($book->discount_price) && $book->discount_price < $book->price,
            'discount_percentage' => $this->calculateDiscountPercentage($book->price, $book->discount_price),
            'is_free' => (bool) $book->is_free,
            'features' => json_decode($book->features, true) ?? [],
            
            // از cache استفاده می‌کنیم (بدون JOIN!)
            'authors' => json_decode($book->authors_cache, true) ?? [],
            'categories' => json_decode($book->categories_cache, true) ?? [],
            
            // Primary category
            'primary_category' => $book->primary_category_id ? [
                'id' => $book->primary_category_id,
                'name' => $book->primary_category_name,
                'slug' => $book->primary_category_slug,
            ] : null,
            
            // Publisher
            'publisher' => $book->publisher_id ? [
                'id' => $book->publisher_id,
                'name' => $book->publisher_name,
            ] : null,
            
            // آمار از جدول جدا
            'rating' => $stats ? (float) $stats->rating : 0,
            'rating_count' => $stats ? $stats->rating_count : 0,
            'purchase_count' => $stats ? $stats->purchase_count : 0,
            'view_count' => $stats ? $stats->view_count : 0,
            'favorite_count' => $stats ? $stats->favorite_count : 0,
            
            // فهرست
            'index' => $index,
            
            // Subscription plans (اگر نیاز بود)
            'subscription_plans' => $book->primary_category_id 
                ? $this->getCategoryPlans($book->primary_category_id) 
                : [],
            
            'created_at' => $book->created_at,
        ];
        
        // Cache in DB (Layer 2)
        BookDetailCache::updateOrCreate(
            ['book_id' => $bookId],
            [
                'payload' => $data,
                'updated_at' => now(),
            ]
        );
        
        // Cache in Redis (Layer 1)
        Cache::put($cacheKey, json_encode($data), self::REDIS_TTL);
        
        // Increment view count (async, بدون تاثیر روی سرعت)
        dispatch(function () use ($bookId) {
            DB::table('book_stats')
                ->where('book_id', $bookId)
                ->increment('view_count');
        })->afterResponse();
        
        return array_merge($data, ['source' => 'database']);
    }
    
    /**
     * محاسبه درصد تخفیف
     */
    private function calculateDiscountPercentage(?float $price, ?float $discountPrice): float
    {
        if (!$discountPrice || !$price || $price == 0) {
            return 0;
        }
        
        return round((($price - $discountPrice) / $price) * 100, 2);
    }
    
    /**
     * دریافت پلن‌های اشتراک دسته (cached)
     */
    private function getCategoryPlans(int $categoryId): array
    {
        $cacheKey = "category:plans:{$categoryId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($categoryId) {
            return DB::table('subscription_plans')
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('priority')
                ->get([
                    'id',
                    'duration_months',
                    'price',
                    'discount_percentage'
                ])
                ->toArray();
        });
    }
    
    /**
     * Invalidate all cache layers
     */
    public function invalidateCache(int $bookId): void
    {
        // Clear Redis
        Cache::forget("book:ultra:detail:{$bookId}");
        Cache::forget("book:details:{$bookId}");
        Cache::forget("book:index:{$bookId}");
        
        // Mark DB cache as outdated (don't delete, just update timestamp)
        DB::table('book_detail_cache')
            ->where('book_id', $bookId)
            ->update(['updated_at' => now()->subDays(2)]);
    }
    
    /**
     * Warm up cache for popular books
     */
    public function warmUpPopularBooks(int $limit = 100): int
    {
        $popularBookIds = DB::table('book_stats')
            ->join('books', 'book_stats.book_id', '=', 'books.id')
            ->where('books.status', 'published')
            ->orderBy('book_stats.view_count', 'desc')
            ->limit($limit)
            ->pluck('book_stats.book_id');
        
        $count = 0;
        foreach ($popularBookIds as $bookId) {
            $this->getBookDetail($bookId);
            $count++;
        }
        
        return $count;
    }
}

