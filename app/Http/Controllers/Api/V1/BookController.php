<?php


namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\BookDetailRequest;
use App\Http\Requests\Book\BookListRequest;
use App\Http\Requests\Book\ReadContentRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookListResource;
use App\Http\Resources\PageContentResource;
use App\Services\BookService;
use App\DTOs\Book\BookDetailDTO;
use App\DTOs\Book\BookListDTO;
use App\DTOs\Book\ReadContentDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
    ) {}

    /**
     * دریافت جزئیات کتاب
     *
     * @param BookDetailRequest $request
     * @return JsonResponse
     */
    public function detail(BookDetailRequest $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $dto = BookDetailDTO::fromRequest($request->validated(), $userId);

            $result = $this->bookService->getBookDetail($dto);

            return response()->json([
                'success' => true,
                'data' => new BookDetailResource($result['book']),
                'user_access' => $result['user_access'],
                'meta' => [
                    'source' => $result['source'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Book Detail Error', [
                'message' => $e->getMessage(),
                'request' => $request->validated(),
            ]);

            $statusCode = $e->getCode() ?: 500;
            if (!in_array($statusCode, [400, 401, 403, 404, 422])) {
                $statusCode = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * لیست کتاب‌ها با فیلتر و pagination
     *
     * @param BookListRequest $request
     * @return JsonResponse
     */
    public function index(BookListRequest $request): JsonResponse
    {
        try {
            $dto = BookListDTO::fromRequest($request->validated());

            $books = $this->bookService->getBookList($dto);

            return response()->json([
                'success' => true,
                'data' => BookListResource::collection($books->items()),
                'meta' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'per_page' => $books->perPage(),
                    'total' => $books->total(),
                    'from' => $books->firstItem(),
                    'to' => $books->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Book List Error', [
                'message' => $e->getMessage(),
                'request' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست کتاب‌ها',
            ], 500);
        }
    }

    /**
     * خواندن محتوای یک صفحه
     *
     * @param ReadContentRequest $request
     * @return JsonResponse
     */
    public function readPage(ReadContentRequest $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $dto = ReadContentDTO::fromRequest($request->validated(), $userId);

            $content = $this->bookService->getPageContent($dto);

            return response()->json([
                'success' => true,
                'data' => new PageContentResource($content),
            ]);

        } catch (\Exception $e) {
            Log::error('Read Page Error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->validated(),
            ]);


$statusCode = $e->getCode() ?: 500;
            if (!in_array($statusCode, [400, 401, 403, 404, 422])) {
                $statusCode = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * دریافت فهرست کتاب
     *
     * @param int $bookId
     * @return JsonResponse
     */
    public function getIndex(int $bookId): JsonResponse
    {
        try {
            $dto = BookDetailDTO::fromRequest(['id' => $bookId]);
            $result = $this->bookService->getBookDetail($dto);

            return response()->json([
                'success' => true,
                'data' => [
                    'book_id' => $bookId,
                    'index' => $result['book']['index'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Book Index Error', [
                'message' => $e->getMessage(),
                'book_id' => $bookId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فهرست کتاب',
            ], 500);
        }
    }

    /**
     * پاک کردن cache یک کتاب (Admin only)
     *
     * @param int $bookId
     * @return JsonResponse
     */
    public function clearCache(int $bookId): JsonResponse
    {
        try {
            $this->bookService->clearBookCache($bookId);

            return response()->json([
                'success' => true,
                'message' => 'کش کتاب پاک شد',
            ]);

        } catch (\Exception $e) {
            Log::error('Clear Cache Error', [
                'message' => $e->getMessage(),
                'book_id' => $bookId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پاک کردن کش',
            ], 500);
        }
    }
}
