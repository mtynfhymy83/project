<?php

namespace App\Services;

use App\Models\UserLibrary;
use App\Models\Book;
use App\Models\ReadingSession;
use App\Models\Bookmark;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LibraryService
{
    private const LIBRARY_CACHE_TTL = 3600; // 1 hour

    /**
     * دریافت کتابخانه کاربر
     */
    public function getUserLibrary(int $userId, ?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $cacheKey = "user:library:{$userId}:{$status}:{$page}:{$perPage}";

        return Cache::remember($cacheKey, self::LIBRARY_CACHE_TTL, function () use ($userId, $status, $page, $perPage) {
            $query = UserLibrary::with(['book.primaryCategory', 'book.authors'])
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('access_expires_at')
                        ->orWhere('access_expires_at', '>', now());
                });

            if ($status) {
                $query->where('status', $status);
            }

            $library = $query->orderBy('last_read_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'books' => $library->items(),
                'pagination' => [
                    'current_page' => $library->currentPage(),
                    'last_page' => $library->lastPage(),
                    'per_page' => $library->perPage(),
                    'total' => $library->total(),
                ],
            ];
        });
    }

    /**
     * بروزرسانی پیشرفت خواندن
     */
    public function updateProgress(int $userId, int $bookId, int $pageNumber, int $paragraphNumber = 1): array
    {
        $library = UserLibrary::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->firstOrFail();

        $book = Book::findOrFail($bookId);

        // محاسبه درصد پیشرفت
        $progress = $book->pages > 0 ? min(100, ($pageNumber / $book->pages) * 100) : 0;

        // تعیین وضعیت
        $status = 'reading';
        if ($progress >= 100) {
            $status = 'completed';
        } elseif ($library->status === 'not_started') {
            $status = 'reading';
        }

        $library->update([
            'current_page' => $pageNumber,
            'current_paragraph' => $paragraphNumber,
            'progress_percentage' => round($progress, 2),
            'status' => $status,
            'last_read_at' => now(),
            'completed_at' => $status === 'completed' ? now() : $library->completed_at,
        ]);

        // پاک کردن cache
        $this->clearLibraryCache($userId);

        return [
            'current_page' => $pageNumber,
            'progress_percentage' => round($progress, 2),
            'status' => $status,
        ];
    }

    /**
     * شروع جلسه خواندن
     */
    public function startReadingSession(int $userId, int $bookId, ?string $deviceType = null, ?string $platform = null): ReadingSession
    {
        return ReadingSession::create([
            'user_id' => $userId,
            'book_id' => $bookId,
            'started_at' => now(),
            'device_type' => $deviceType,
            'platform' => $platform,
        ]);
    }

    /**
     * پایان جلسه خواندن
     */
    public function endReadingSession(int $sessionId, int $startPage, int $endPage): void
    {
        $session = ReadingSession::findOrFail($sessionId);

        $duration = now()->diffInSeconds($session->started_at);
        $pagesRead = max(0, $endPage - $startPage + 1);

        $session->update([
            'ended_at' => now(),
            'duration' => $duration,
            'start_page' => $startPage,
            'end_page' => $endPage,
            'pages_read' => $pagesRead,
        ]);

        // بروزرسانی آمار کلی
        $library = UserLibrary::where('user_id', $session->user_id)
            ->where('book_id', $session->book_id)
            ->first();


if ($library) {
    $library->increment('total_reading_time', $duration);
    $library->increment('session_count');
    $library->increment('total_pages_read', $pagesRead);
}

        $this->clearLibraryCache($session->user_id);
    }

    /**
     * افزودن نشانک
     */
    public function addBookmark(int $userId, int $bookId, int $pageNumber, ?string $note = null, ?string $color = 'yellow'): Bookmark
    {
        // دریافت عنوان فصل در صورت وجود
        $chapterTitle = DB::table('book_contents')
            ->where('book_id', $bookId)
            ->where('page_number', '<=', $pageNumber)
            ->where('is_index', true)
            ->orderBy('page_number', 'desc')
            ->value('index_title');

        $bookmark = Bookmark::create([
            'user_id' => $userId,
            'book_id' => $bookId,
            'page_number' => $pageNumber,
            'chapter_title' => $chapterTitle,
            'note' => $note,
            'color' => $color,
        ]);

        $this->clearLibraryCache($userId);

        return $bookmark;
    }

    /**
     * دریافت نشانک‌های کتاب
     */
    public function getBookmarks(int $userId, int $bookId): array
    {
        return Bookmark::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->orderBy('page_number')
            ->get()
            ->map(fn($bookmark) => [
                'id' => $bookmark->id,
                'page_number' => $bookmark->page_number,
                'chapter_title' => $bookmark->chapter_title,
                'note' => $bookmark->note,
                'color' => $bookmark->color,
                'created_at' => $bookmark->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * حذف نشانک
     */
    public function deleteBookmark(int $userId, int $bookmarkId): bool
    {
        $bookmark = Bookmark::where('id', $bookmarkId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $this->clearLibraryCache($userId);

        return $bookmark->delete();
    }

    /**
     * دریافت آمار خواندن
     */
    public function getReadingStats(int $userId): array
    {
        $cacheKey = "user:stats:{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            $library = UserLibrary::where('user_id', $userId)->get();

            $totalBooks = $library->count();
            $completedBooks = $library->where('status', 'completed')->count();
            $readingBooks = $library->where('status', 'reading')->count();
            $notStartedBooks = $library->where('status', 'not_started')->count();

            $totalReadingTime = $library->sum('total_reading_time'); // seconds
            $totalPagesRead = $library->sum('total_pages_read');
            $totalSessions = $library->sum('session_count');

            // آمار 30 روز اخیر
            $recentSessions = ReadingSession::where('user_id', $userId)
                ->where('started_at', '>=', now()->subDays(30))
                ->get();

            $last30DaysTime = $recentSessions->sum('duration');
            $last30DaysPages = $recentSessions->sum('pages_read');

            return [
                'total_books' => $totalBooks,
                'completed_books' => $completedBooks,
                'reading_books' => $readingBooks,
                'not_started_books' => $notStartedBooks,
                'completion_rate' => $totalBooks > 0 ? round(($completedBooks / $totalBooks) * 100, 1) : 0,
                'total_reading_time' => $totalReadingTime, // seconds
                'total_reading_time_formatted' => $this->formatDuration($totalReadingTime),
                'total_pages_read' => $totalPagesRead,
                'total_sessions' => $totalSessions,

'average_session_time' => $totalSessions > 0 ? round($totalReadingTime / $totalSessions) : 0,
                'last_30_days' => [
                'reading_time' => $last30DaysTime,
                'reading_time_formatted' => $this->formatDuration($last30DaysTime),
                'pages_read' => $last30DaysPages,
                'sessions_count' => $recentSessions->count(),
            ],
            ];
        });
    }

    /**
     * دریافت فعالیت اخیر
     */
    public function getRecentActivity(int $userId, int $limit = 10): array
    {
        return ReadingSession::with('book:id,title,cover_image')
            ->where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($session) => [
                'book_id' => $session->book_id,
                'book_title' => $session->book->title,
                'book_cover' => $session->book->cover_url,
                'started_at' => $session->started_at->toIso8601String(),
                'duration' => $session->duration,
                'duration_formatted' => $this->formatDuration($session->duration),
                'pages_read' => $session->pages_read,
            ])
            ->toArray();
    }

    /**
     * تنظیمات خواندن کاربر
     */
    public function updateReadingPreferences(int $userId, int $bookId, array $preferences): bool
    {
        $library = UserLibrary::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->firstOrFail();

        $library->update([
            'reading_preferences' => array_merge($library->reading_preferences ?? [], $preferences)
        ]);

        $this->clearLibraryCache($userId);

        return true;
    }

    /**
     * فرمت کردن مدت زمان
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * پاک کردن cache
     */
    private function clearLibraryCache(int $userId): void
    {
        Cache::forget("user:library:{$userId}");
        Cache::forget("user:stats:{$userId}");

        // پاک کردن تمام variations
        foreach (['not_started', 'reading', 'completed', null] as $status) {
            for ($page = 1; $page <= 10; $page++) {
                Cache::forget("user:library:{$userId}:{$status}:{$page}:20");
            }
        }
    }
}
