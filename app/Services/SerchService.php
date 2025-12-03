<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookContent;
use App\Models\Category;
use App\Models\Author;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    private const SEARCH_CACHE_TTL = 1800; // 30 minutes

    /**
     * جستجوی کلی (کتاب‌ها، نویسندگان، دسته‌بندی‌ها)
     */
    public function globalSearch(string $query, int $page = 1, int $perPage = 20): array
    {
        $cacheKey = "search:global:" . md5($query) . ":{$page}:{$perPage}";

        return Cache::remember($cacheKey, self::SEARCH_CACHE_TTL, function () use ($query, $page, $perPage) {
            return [
                'books' => $this->searchBooks($query, 1, $perPage)['results'],
                'authors' => $this->searchAuthors($query, 5),
                'categories' => $this->searchCategories($query, 5),
                'query' => $query,
            ];
        });
    }

    /**
     * جستجوی پیشرفته در کتاب‌ها
     */
    public function searchBooks(
        string $query,
        int $page = 1,
        int $perPage = 20,
        ?array $filters = []
    ): array {
        $cacheKey = $this->getSearchCacheKey('books', $query, $page, $perPage, $filters);

        return Cache::remember($cacheKey, self::SEARCH_CACHE_TTL, function () use ($query, $page, $perPage, $filters) {
            $searchQuery = Book::with(['primaryCategory:id,name', 'authors:id,name'])
                ->published();

            // Full-Text Search با PostgreSQL
            if (!empty($query)) {
                $searchQuery->where(function ($q) use ($query) {
                    // جستجوی Full-Text
                    $q->whereRaw(
                        "to_tsvector('english', title) @@ plainto_tsquery('english', ?)",
                        [$query]
                    )
                        // یا جستجوی Trigram (فازی)
                        ->orWhereRaw("title ILIKE ?", ["%{$query}%"])
                        ->orWhereRaw("content ILIKE ?", ["%{$query}%"]);
                });
            }

            // فیلترها
            if (!empty($filters['category_id'])) {
                $searchQuery->where(function ($q) use ($filters) {
                    $q->where('primary_category_id', $filters['category_id'])
                        ->orWhereHas('categories', fn($qc) => $qc->where('categories.id', $filters['category_id']));
                });
            }

            if (!empty($filters['author_id'])) {
                $searchQuery->whereHas('authors', fn($q) => $q->where('authors.id', $filters['author_id']));
            }

            if (!empty($filters['min_price'])) {
                $searchQuery->where('price', '>=', $filters['min_price']);
            }

            if (!empty($filters['max_price'])) {
                $searchQuery->where('price', '<=', $filters['max_price']);
            }

            if (!empty($filters['is_free'])) {
                $searchQuery->where('is_free', true);
            }

            if (!empty($filters['min_rating'])) {
                $searchQuery->where('rating', '>=', $filters['min_rating']);
            }

            // مرتب‌سازی
            $sort = $filters['sort'] ?? 'relevance';

            switch ($sort) {
                case 'price_asc':
                    $searchQuery->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $searchQuery->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $searchQuery->orderBy('rating', 'desc')->orderBy('rating_count', 'desc');
                    break;
                case 'popular':
                    $searchQuery->orderBy('purchase_count', 'desc');
                    break;
                case 'latest':
                    $searchQuery->orderBy('created_at', 'desc');


break;
                default: // relevance
                    if (!empty($query)) {
                        $searchQuery->orderByRaw(
                            "ts_rank(to_tsvector('english', title), plainto_tsquery('english', ?)) DESC",
                            [$query]
                        );
                    }
            }

            $results = $searchQuery->paginate($perPage, ['*'], 'page', $page);

            return [
                'results' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                ],
            ];
        });
    }

    /**
     * جستجو در محتوای کتاب
     */
    public function searchInBook(int $bookId, string $query, int $page = 1, int $perPage = 20): array
    {
        $cacheKey = "search:book:{$bookId}:" . md5($query) . ":{$page}";

        return Cache::remember($cacheKey, self::SEARCH_CACHE_TTL, function () use ($bookId, $query, $page, $perPage) {
            $results = BookContent::where('book_id', $bookId)
                ->where(function ($q) use ($query) {
                    $q->whereRaw(
                        "to_tsvector('english', text) @@ plainto_tsquery('english', ?)",
                        [$query]
                    )
                        ->orWhere('text', 'ILIKE', "%{$query}%");
                })
                ->orderBy('page_number')
                ->orderBy('paragraph_number')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'book_id' => $bookId,
                'query' => $query,
                'results' => $results->items()->map(fn($content) => [
                    'id' => $content->id,
                    'page_number' => $content->page_number,
                    'paragraph_number' => $content->paragraph_number,
                    'text' => $this->highlightText($content->text, $query),
                    'excerpt' => $this->getExcerpt($content->text, $query),
                ]),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'total' => $results->total(),
                ],
            ];
        });
    }

    /**
     * جستجوی نویسندگان
     */
    public function searchAuthors(string $query, int $limit = 10): array
    {
        return Author::where('name', 'ILIKE', "%{$query}%")
            ->where('is_active', true)
            ->orderByRaw("similarity(name, ?) DESC", [$query])
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'avatar'])
            ->toArray();
    }

    /**
     * جستجوی دسته‌بندی‌ها
     */
    public function searchCategories(string $query, int $limit = 10): array
    {
        return Category::where('name', 'ILIKE', "%{$query}%")
            ->where('is_active', true)
            ->orderByRaw("similarity(name, ?) DESC", [$query])
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'icon'])
            ->toArray();
    }

    /**
     * پیشنهادات جستجو (Autocomplete)
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        $cacheKey = "search:suggestions:" . md5($query);

        return Cache::remember($cacheKey, self::SEARCH_CACHE_TTL, function () use ($query, $limit) {
            // جستجو در عنوان کتاب‌ها
            $bookSuggestions = Book::published()
                ->where('title', 'ILIKE', "%{$query}%")
                ->orderByRaw("similarity(title, ?) DESC", [$query])
                ->limit($limit)
                ->pluck('title')
                ->toArray();


// جستجو در نام نویسندگان
            $authorSuggestions = Author::where('is_active', true)
                ->where('name', 'ILIKE', "%{$query}%")
                ->orderByRaw("similarity(name, ?) DESC", [$query])
                ->limit(5)
                ->pluck('name')
                ->toArray();

            return array_values(array_unique(array_merge($bookSuggestions, $authorSuggestions)));
        });
    }

    /**
     * پیشنهادات مرتبط (Related Books)
     */
    public function getRelatedBooks(int $bookId, int $limit = 6): array
    {
        $cacheKey = "book:related:{$bookId}:{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($bookId, $limit) {
            $book = Book::findOrFail($bookId);

            // کتاب‌های هم دسته
            $related = Book::published()
                ->where('id', '!=', $bookId)
                ->where(function ($q) use ($book) {
                    $q->where('primary_category_id', $book->primary_category_id)
                        ->orWhereHas('categories', function ($qc) use ($book) {
                            $qc->whereIn('categories.id', $book->categories->pluck('id'));
                        });
                })
                ->with(['primaryCategory:id,name', 'authors:id,name'])
                ->orderBy('rating', 'desc')
                ->orderBy('purchase_count', 'desc')
                ->limit($limit)
                ->get();

            return $related->toArray();
        });
    }

    /**
     * جستجوی محبوب (Popular Searches)
     */
    public function getPopularSearches(int $limit = 10): array
    {
        $cacheKey = "search:popular:{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($limit) {
            // این باید از یک جدول search_logs گرفته بشه
            // فعلاً یک mock ساده:
            return DB::table('search_logs')
                ->select('query', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('query')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->pluck('query')
                ->toArray();
        });
    }

    /**
     * ثبت جستجو (برای آمار)
     */
    public function logSearch(string $query, ?int $userId = null, int $resultsCount = 0): void
    {
        DB::table('search_logs')->insert([
            'user_id' => $userId,
            'query' => $query,
            'results_count' => $resultsCount,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * هایلایت کردن متن
     */
    private function highlightText(string $text, string $query): string
    {
        $pattern = '/(' . preg_quote($query, '/') . ')/iu';
        return preg_replace($pattern, '<mark>$1</mark>', $text);
    }

    /**
     * استخراج excerpt
     */
    private function getExcerpt(string $text, string $query, int $length = 200): string
    {
        $position = mb_stripos($text, $query);

        if ($position === false) {
            return mb_substr($text, 0, $length) . '...';
        }

        $start = max(0, $position - 50);
        $excerpt = mb_substr($text, $start, $length);

        if ($start > 0) {
            $excerpt = '...' . $excerpt;
        }

        if (mb_strlen($text) > $start + $length) {
            $excerpt .= '...';
        }

        return $this->highlightText($excerpt, $query);
    }

    /**
     * تولید cache key
     */
    private function getSearchCacheKey(string $type, string $query, int $page, int $perPage, ?array $filters): string
    {
        $filterKey = $filters ? md5(json_encode($filters)) : 'nofilter';
        return "search:{$type}:" . md5($query) . ":{$page}:{$perPage}:{$filterKey}";
    }
}
