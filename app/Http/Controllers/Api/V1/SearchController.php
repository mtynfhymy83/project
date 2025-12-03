<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService
    ) {}

    /**
     * جستجوی کلی
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $query = $request->input('q');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);

            $result = $this->searchService->globalSearch($query, $page, $perPage);

            // ثبت جستجو
            $this->searchService->logSearch(
                query: $query,
                userId: auth()->id(),
                resultsCount: count($result['books'])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Global Search Error', [
                'query' => $request->input('q'),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجو',
            ], 500);
        }
    }

    /**
     * جستجوی پیشرفته کتاب‌ها
     */
    public function searchBooks(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'author_id' => 'nullable|integer|exists:authors,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'is_free' => 'nullable|boolean',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'sort' => 'nullable|string|in:relevance,price_asc,price_desc,rating,popular,latest',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $query = $request->input('q', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);

            $filters = $request->only([
                'category_id', 'author_id', 'min_price', 'max_price',
                'is_free', 'min_rating', 'sort'
            ]);

            $result = $this->searchService->searchBooks($query, $page, $perPage, $filters);

            if ($query) {
                $this->searchService->logSearch(
                    query: $query,
                    userId: auth()->id(),
                    resultsCount: $result['pagination']['total']
                );
            }

            return response()->json([
                'success' => true,
                'data' => $result['results'],
                'meta' => $result['pagination'],
            ]);

        } catch (\Exception $e) {
            Log::error('Search Books Error', [
                'request' => $request->all(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجو',
            ], 500);
        }
    }

    /**
     * جستجو در محتوای کتاب
     */
    public function searchInBook(int $bookId, Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);


try {
    $result = $this->searchService->searchInBook(
        bookId: $bookId,
        query: $request->input('q'),
        page: $request->input('page', 1),
        perPage: $request->input('per_page', 20)
    );

    return response()->json([
        'success' => true,
        'data' => $result,
    ]);

} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'message' => 'خطا در جستجو',
    ], 500);
}
    }

    /**
     * پیشنهادات (Autocomplete)
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        try {
            $suggestions = $this->searchService->getSuggestions(
                query: $request->input('q'),
                limit: 10
            );

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پیشنهادات',
            ], 500);
        }
    }

    /**
     * کتاب‌های مرتبط
     */
    public function relatedBooks(int $bookId): JsonResponse
    {
        try {
            $related = $this->searchService->getRelatedBooks($bookId, 6);

            return response()->json([
                'success' => true,
                'data' => $related,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت کتاب‌های مرتبط',
            ], 500);
        }
    }

    /**
     * جستجوهای محبوب
     */
    public function popularSearches(): JsonResponse
    {
        try {
            $popular = $this->searchService->getPopularSearches(10);

            return response()->json([
                'success' => true,
                'data' => $popular,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جستجوهای محبوب',
            ], 500);
        }
    }
}
