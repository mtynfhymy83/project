<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookContent;
use App\Models\User_Library;
use App\Models\UserSubscription;
use App\Models\Purchase;
use App\DTOs\Book\BookDetailDTO;
use App\DTOs\Book\BookListDTO;
use App\DTOs\Book\ReadContentDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class BookService
{
    // Cache TTL
    private const BOOK_DETAIL_CACHE_TTL = 21600; // 6 hours
    private const BOOK_LIST_CACHE_TTL = 3600; // 1 hour
    private const CONTENT_CACHE_TTL = 43200; // 12 hours
    private const INDEX_CACHE_TTL = 86400; // 24 hours

    /**
     * دریافت جزئیات کتاب (Ultra-Fast با 3-Layer Cache)
     * Performance: 1-5ms (از Redis/DB cache) یا 50ms (load اول)
     */
    public function getBookDetail(BookDetailDTO $dto): array
    {
        // استفاده از Fast Cache Service
        $fastCache = app(FastBookCacheService::class);
        $bookData = $fastCache->getBookDetail($dto->id);

        if (!$bookData) {
            throw new \Exception('کتاب یافت نشد', 404);
        }

        // اطلاعات دسترسی کاربر (cached جداگانه برای هر کاربر)
        $userAccess = null;
        if ($dto->userId) {
            $userAccess = $this->getUserBookAccess($dto->userId, $dto->id);
        }

        return [
            'book' => $bookData,
            'user_access' => $userAccess,
            'source' => $bookData['source'] ?? 'unknown',
        ];
    }

    /**
     * بارگذاری کتاب از دیتابیس با Eager Loading
     */
    private function loadBookFromDatabase(int $bookId): ?array
    {
        $book = Book::with([
            'primaryCategory:id,name,slug',
            'categories:id,name,slug',
            'authors:id,name,slug',
            'publisher:id,name',
        ])
            ->where('id', $bookId)
            ->where('status', 'published')
            ->first();

        if (!$book) {
            return null;
        }

        // افزایش view count (async در background)
        dispatch(function () use ($book) {
            $book->incrementViews();
        })->afterResponse();

        // دریافت فهرست کتاب (cached جداگانه)
        $index = $this->getBookIndex($bookId);

        // دریافت اطلاعات اشتراک دسته‌بندی
        $subscriptionPlans = [];
        if ($book->primaryCategory) {
            $subscriptionPlans = $this->getCategorySubscriptionPlans($book->primaryCategory->id);
        }

        return [
            'id' => $book->id,
            'title' => $book->title,
            'slug' => $book->slug,
            'excerpt' => $book->excerpt,
            'content' => $book->content,
            'cover_url' => $book->cover_url,
            'thumbnail_url' => $book->thumbnail_url,
            'pages' => $book->pages,
            'price' => (float) $book->price,
            'discount_price' => $book->discount_price ? (float) $book->discount_price : null,
            'effective_price' => (float) $book->getEffectivePrice(),
            'has_discount' => $book->hasDiscount(),
            'discount_percentage' => $book->getDiscountPercentage(),
            'is_free' => $book->is_free,
            'rating' => (float) $book->rating,
            'rating_count' => $book->rating_count,
            'purchase_count' => $book->purchase_count,
            'features' => [
                'has_description' => $book->has_description,
                'has_sound' => $book->has_sound,
                'has_video' => $book->has_video,
                'has_image' => $book->has_image,
                'has_test' => $book->has_test,
                'has_essay' => $book->has_essay,
                'has_download' => $book->has_download,
            ],
            'primary_category' => $book->primaryCategory ? [
                'id' => $book->primaryCategory->id,


'name' => $book->primaryCategory->name,
                'slug' => $book->primaryCategory->slug,
            ] : null,
            'categories' => $book->categories->map(fn($cat) => [
        'id' => $cat->id,
        'name' => $cat->name,
        'slug' => $cat->slug,
    ])->toArray(),
            'authors' => $book->authors->map(fn($author) => [
        'id' => $author->id,
        'name' => $author->name,
        'slug' => $author->slug,
    ])->toArray(),
            'publisher' => $book->publisher ? [
        'id' => $book->publisher->id,
        'name' => $book->publisher->name,
    ] : null,
            'index' => $index,
            'subscription_plans' => $subscriptionPlans,
            'created_at' => $book->created_at?->toIso8601String(),
        ];
    }

    /**
     * دریافت دسترسی کاربر به کتاب
     */
    private function getUserBookAccess(int $userId, int $bookId): ?array
    {
        $cacheKey = "user:{$userId}:book:{$bookId}:access";
        
        // Cache access check برای 5 دقیقه
        return Cache::remember($cacheKey, 300, function () use ($userId, $bookId) {
            // بررسی خرید مستقیم (کوئری بهینه)
            $hasPurchased = DB::table('purchases')
                ->where('user_id', $userId)
                ->where('book_id', $bookId)
                ->where('status', 'completed')
                ->exists();

            if ($hasPurchased) {
                return [
                    'has_access' => true,
                    'access_type' => 'purchased',
                ];
            }

            // بررسی دسترسی از طریق اشتراک (کوئری بهینه)
            $subscription = DB::table('user_subscriptions as us')
                ->join('books as b', 'us.category_id', '=', 'b.primary_category_id')
                ->where('b.id', $bookId)
                ->where('us.user_id', $userId)
                ->where('us.is_active', true)
                ->where('us.expires_at', '>', now())
                ->first(['us.id', 'us.expires_at']);

            if ($subscription) {
                return [
                    'has_access' => true,
                    'access_type' => 'subscription',
                    'expires_at' => $subscription->expires_at,
                    'subscription_id' => $subscription->id,
                ];
            }

            return [
                'has_access' => false,
                'access_type' => null,
            ];
        });
    }

    /**
     * Old getUserBookAccess - kept for reference
     */
    private function getUserBookAccessOld(int $userId, int $bookId): ?array
    {
        // ابتدا بررسی کنیم آیا خرید مستقیم داره
        $library = User_Library::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->first();

        if ($library) {
            return [
                'has_access' => true,
                'access_type' => $library->access_type,
                'expires_at' => $library->access_expires_at?->toIso8601String(),
                'progress' => [
                    'current_page' => $library->current_page,
                    'progress_percentage' => (float) $library->progress_percentage,
                    'status' => $library->status,
                    'last_read_at' => $library->last_read_at?->toIso8601String(),
                ],
            ];
        }

        // بررسی اشتراک دسته‌بندی
        $book = Book::findOrFail($bookId);
        if ($book->primary_category_id) {
            $subscription = UserSubscription::where('user_id', $userId)
                ->where('category_id', $book->primary_category_id)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if ($subscription) {
                return [
                    'has_access' => true,
                    'access_type' => 'subscription',
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                    'subscription_id' => $subscription->id,
                ];
            }
        }

        return [
            'has_access' => false,
            'access_type' => null,
        ];
    }

    /**
     * دریافت فهرست کتاب (cached)
     */
    private function getBookIndex(int $bookId): array
    {
        $cacheKey = "book:index:{$bookId}";

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL, function () use ($bookId) {
            return BookContent::where('book_id', $bookId)
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
        });
    }

    /**
     * دریافت طرح‌های اشتراک دسته‌بندی
     */
    private function getCategorySubscriptionPlans(int $categoryId): array
    {
        $cacheKey = "category:subscriptions:{$categoryId}";

        return Cache::remember($cacheKey, self::BOOK_DETAIL_CACHE_TTL, function () use ($categoryId) {


return DB::table('subscription_plans')
    ->where('category_id', $categoryId)
    ->where('is_active', true)
    ->orderBy('priority')
    ->get(['id', 'duration_months', 'price', 'discount_percentage'])
    ->map(fn($plan) => [
        'id' => $plan->id,
        'duration_months' => $plan->duration_months,
        'price' => (float) $plan->price,
        'discount_percentage' => (float) $plan->discount_percentage,
        'final_price' => (float) ($plan->price * (1 - $plan->discount_percentage / 100)),
    ])
    ->toArray();
        });
    }

    /**
     * لیست کتاب‌ها با فیلتر و pagination
     */
    public function getBookList(BookListDTO $dto): LengthAwarePaginator
    {
        $cacheKey = $this->getListCacheKey($dto);

        // برای list ها، cache کوتاه‌تر
        return Cache::remember($cacheKey, self::BOOK_LIST_CACHE_TTL, function () use ($dto) {
            $query = Book::with(['primaryCategory:id,name,slug', 'authors:id,name'])
                ->published();

            // فیلتر دسته‌بندی
            if ($dto->categoryId) {
                $query->byCategory($dto->categoryId);
            }

            // جستجو
            if ($dto->search) {
                $query->search($dto->search);
            }

            // فیلتر رایگان
            if ($dto->freeOnly) {
                $query->free();
            }

            // فیلتر ویژه
            if ($dto->specialOnly) {
                $query->special();
            }

            // مرتب‌سازی
            match ($dto->sort) {
                'popular' => $query->popular(),
                'rating' => $query->topRated(),
                default => $query->latest(),
            };

            return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
        });
    }

    /**
     * خواندن محتوای یک صفحه (بهینه‌سازی شده)
     */
    public function getPageContent(ReadContentDTO $dto): array
    {
        // بررسی دسترسی کاربر
        if ($dto->userId) {
            $access = $this->getUserBookAccess($dto->userId, $dto->bookId);
            if (!$access['has_access']) {
                throw new \Exception('شما به این کتاب دسترسی ندارید', 403);
            }
        } else {
            throw new \Exception('لطفاً وارد شوید', 401);
        }

        $cacheKey = "book:content:{$dto->bookId}:page:{$dto->pageNumber}";

        $content = Cache::remember($cacheKey, self::CONTENT_CACHE_TTL, function () use ($dto) {
            return BookContent::where('book_id', $dto->bookId)
                ->where('page_number', $dto->pageNumber)
                ->orderBy('paragraph_number')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'paragraph_number' => $item->paragraph_number,
                    'text' => $item->text,
                    'description' => $item->description,
                    'sound_url' => $item->getSoundUrl(),
                    'video_url' => $item->getVideoUrl(),
                    'image_urls' => $item->getImageUrls(),
                    'is_index' => $item->is_index,
                    'index_title' => $item->index_title,
                ])
                ->toArray();
        });

        // بروزرسانی progress کاربر (async)
        if ($dto->userId) {
            dispatch(function () use ($dto) {
                $this->updateReadingProgress($dto->userId, $dto->bookId, $dto->pageNumber);
            })->afterResponse();
        }

        return [
            'book_id' => $dto->bookId,
            'page_number' => $dto->pageNumber,
            'content' => $content,
        ];
    }

    /**
     * بروزرسانی پیشرفت خواندن
     */
    private function updateReadingProgress(int $userId, int $bookId, int $pageNumber): void
    {
        $library = User_Library::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->first();

if ($library) {
    $book = Book::find($bookId);
    $progress = $book->pages > 0 ? ($pageNumber / $book->pages) * 100 : 0;

    $library->update([
        'current_page' => $pageNumber,
        'progress_percentage' => min(100, $progress),
        'status' => $progress >= 100 ? 'completed' : 'reading',
        'last_read_at' => now(),
    ]);
}
    }

    /**
     * پاک کردن cache کتاب
     */
    public function clearBookCache(int $bookId): void
    {
        // پاک کردن همه cache layers
        $fastCache = app(FastBookCacheService::class);
        $fastCache->invalidateCache($bookId);
        
        Cache::forget("book:detail:{$bookId}");
        Cache::forget("book:index:{$bookId}");

        // پاک کردن cache محتوا
        $book = Book::find($bookId);
        if ($book) {
            for ($i = 1; $i <= $book->pages; $i++) {
                Cache::forget("book:content:{$bookId}:page:{$i}");
            }
        }
    }

    /**
     * تولید cache key برای لیست
     */
    private function getListCacheKey(BookListDTO $dto): string
    {
        return sprintf(
            'books:list:%s:%s:%s:%d:%d:%d:%d',
            $dto->categoryId ?? 'all',
            $dto->search ? md5($dto->search) : 'nosearch',
            $dto->sort,
            $dto->freeOnly ? 1 : 0,
            $dto->specialOnly ? 1 : 0,
            $dto->page,
            $dto->perPage
        );
    }
}
