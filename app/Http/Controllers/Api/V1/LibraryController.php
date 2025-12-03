<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryController extends Controller
{
    public function __construct(
        private readonly LibraryService $libraryService
    ) {}

    /**
     * کتابخانه من
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:not_started,reading,completed',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $result = $this->libraryService->getUserLibrary(
                userId: auth()->id(),
                status: $request->input('status'),
                page: $request->input('page', 1),
                perPage: $request->input('per_page', 20)
            );

            return response()->json([
                'success' => true,
                'data' => $result['books'],
                'meta' => $result['pagination'],
            ]);

        } catch (\Exception $e) {
            Log::error('Library Index Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت کتابخانه',
            ], 500);
        }
    }

    /**
     * بروزرسانی پیشرفت
     */
    public function updateProgress(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'page_number' => 'required|integer|min:1',
            'paragraph_number' => 'nullable|integer|min:1',
        ]);

        try {
            $result = $this->libraryService->updateProgress(
                userId: auth()->id(),
                bookId: $request->input('book_id'),
                pageNumber: $request->input('page_number'),
                paragraphNumber: $request->input('paragraph_number', 1)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Update Progress Error', [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بروزرسانی پیشرفت',
            ], 500);
        }
    }

    /**
     * شروع جلسه خواندن
     */
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'device_type' => 'nullable|string|max:50',
            'platform' => 'nullable|string|max:50',
        ]);

        try {
            $session = $this->libraryService->startReadingSession(
                userId: auth()->id(),
                bookId: $request->input('book_id'),
                deviceType: $request->input('device_type'),
                platform: $request->input('platform')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->id,
                    'started_at' => $session->started_at->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در شروع جلسه',
            ], 500);
        }
    }

    /**
     * پایان جلسه خواندن

    متین, [12/3/2025 9:54 AM]
     */
    public function endSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|integer|exists:reading_sessions,id',
            'start_page' => 'required|integer|min:1',
            'end_page' => 'required|integer|min:1',
        ]);

        try {
            $this->libraryService->endReadingSession(
                sessionId: $request->input('session_id'),
                startPage: $request->input('start_page'),
                endPage: $request->input('end_page')
            );

            return response()->json([
                'success' => true,
                'message' => 'جلسه با موفقیت ثبت شد',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در پایان جلسه',
            ], 500);
        }
    }

    /**
     * آمار خواندن
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->libraryService->getReadingStats(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار',
            ], 500);
        }
    }

    /**
     * فعالیت اخیر
     */
    public function recentActivity(Request $request): JsonResponse
    {
        try {
            $limit = min(50, max(1, $request->input('limit', 10)));
            $activity = $this->libraryService->getRecentActivity(auth()->id(), $limit);

            return response()->json([
                'success' => true,
                'data' => $activity,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فعالیت',
            ], 500);
        }
    }

    /**
     * نشانک‌ها
     */
    public function bookmarks(int $bookId): JsonResponse
    {
        try {
            $bookmarks = $this->libraryService->getBookmarks(auth()->id(), $bookId);

            return response()->json([
                'success' => true,
                'data' => $bookmarks,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت نشانک‌ها',
            ], 500);
        }
    }

    /**
     * افزودن نشانک
     */
    public function addBookmark(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'page_number' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
            'color' => 'nullable|string|in:yellow,green,blue,red,purple',
        ]);

        try {
            $bookmark = $this->libraryService->addBookmark(
                userId: auth()->id(),
                bookId: $request->input('book_id'),
                pageNumber: $request->input('page_number'),
                note: $request->input('note'),
                color: $request->input('color', 'yellow')
            );

            return response()->json([
                'success' => true,
                'data' => $bookmark,
                'message' => 'نشانک با موفقیت اضافه شد',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در افزودن نشانک',
            ], 500);
        }
    }

    /**
     * حذف نشانک
     */
    public function deleteBookmark(int $bookmarkId): JsonResponse
    {
        try {
            $this->libraryService->deleteBookmark(auth()->id(), $bookmarkId);

            return response()->json([
                'success' => true,
                'message' => 'نشانک حذف شد',
            ]);


} catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف نشانک',
            ], 500);
        }
    }
}
