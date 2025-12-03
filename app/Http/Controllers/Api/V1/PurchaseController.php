<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\DTOs\Purchase\PurchaseBookDTO;
use App\DTOs\Purchase\PurchaseSubscriptionDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService
    ) {}

    /**
     * خرید کتاب
     */
    public function purchaseBook(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'coupon_code' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:100',
        ]);

        try {
            $userId = auth()->id();
            $dto = PurchaseBookDTO::fromRequest($request->all(), $userId);

            $result = $this->purchaseService->initiatePurchase($dto);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Purchase Book Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
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
     * خرید اشتراک
     */
    public function purchaseSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_plan_id' => 'required|integer|exists:subscription_plans,id',
            'coupon_code' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:100',
        ]);

        try {
            $userId = auth()->id();
            $dto = PurchaseSubscriptionDTO::fromRequest($request->all(), $userId);

            $result = $this->purchaseService->initiateSubscription($dto);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Purchase Subscription Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
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
     * Callback از درگاه پرداخت
     */
    public function paymentCallback(Request $request): JsonResponse
    {
        try {
            // Parse callback data
            $authority = $request->input('Authority') ?? $request->input('authority');
            $status = $request->input('Status') ?? $request->input('status');

            if ($status !== 'OK') {
                return response()->json([
                    'success' => false,
                    'message' => 'پرداخت لغو شد',
                ], 400);
            }

            // پیدا کردن خرید بر اساس authority
            $purchase = \App\Models\Purchase::whereRaw(
                "payment_details::json->>'authority' = ?",
                [$authority]
            )->firstOrFail();

            // تکمیل خرید
            $this->purchaseService->completePurchase($purchase->id, $authority);

            return response()->json([
                'success' => true,
                'message' => 'پرداخت با موفقیت انجام شد',
                'purchase_id' => $purchase->id,
            ]);


} catch (\Exception $e) {
            Log::error('Payment Callback Error', [
                'request' => $request->all(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در تایید پرداخت',
            ], 500);
        }
    }

    /**
     * لیست خریدهای کاربر
     */
    public function myPurchases(Request $request): JsonResponse
    {
        try {
            $purchases = \App\Models\Purchase::with(['book:id,title,cover_image'])
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $purchases->items(),
                'meta' => [
                    'current_page' => $purchases->currentPage(),
                    'last_page' => $purchases->lastPage(),
                    'total' => $purchases->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست خریدها',
            ], 500);
        }
    }
}
