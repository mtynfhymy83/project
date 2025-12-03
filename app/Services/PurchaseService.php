<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\UserLibrary;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\DTOs\Purchase\PurchaseBookDTO;
use App\DTOs\Purchase\PurchaseSubscriptionDTO;
use App\DTOs\Purchase\PaymentCallbackDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    /**
     * شروع فرآیند خرید کتاب
     */
    public function initiatePurchase(PurchaseBookDTO $dto): array
    {
        DB::beginTransaction();

        try {
            // بررسی کتاب
            $book = Book::findOrFail($dto->bookId);

            if ($book->status !== 'published') {
                throw new \Exception('این کتاب در دسترس نیست', 400);
            }

            // بررسی خرید قبلی
            $existingLibrary = UserLibrary::where('user_id', $dto->userId)
                ->where('book_id', $dto->bookId)
                ->where(function ($q) {
                    $q->whereNull('access_expires_at')
                        ->orWhere('access_expires_at', '>', now());
                })
                ->first();

            if ($existingLibrary) {
                throw new \Exception('شما قبلاً این کتاب را خریداری کرده‌اید', 400);
            }

            // محاسبه قیمت
            $amount = $book->getEffectivePrice();
            $discountAmount = 0;
            $couponId = null;

            // اعمال کوپن
            if ($dto->couponCode) {
                $coupon = $this->validateAndApplyCoupon($dto->couponCode, $dto->userId, $amount);
                $discountAmount = $this->calculateCouponDiscount($coupon, $amount);
                $couponId = $coupon->id;
            }

            $finalAmount = max(0, $amount - $discountAmount);

            // ایجاد رکورد خرید
            $purchase = Purchase::create([
                'user_id' => $dto->userId,
                'purchase_type' => 'book',
                'book_id' => $dto->bookId,
                'subscription_plan_id' => null,
                'transaction_id' => $this->generateTransactionId(),
                'amount' => $amount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'tax_amount' => 0,
                'status' => 'pending',
                'coupon_id' => $couponId,
                'coupon_code' => $dto->couponCode,
            ]);

            // اگر رایگان بود، مستقیم تکمیل کن
            if ($finalAmount == 0) {
                $this->completePurchase($purchase->id, null);

                DB::commit();

                return [
                    'purchase_id' => $purchase->id,
                    'status' => 'completed',
                    'amount' => 0,
                    'message' => 'کتاب با موفقیت به کتابخانه شما اضافه شد',
                ];
            }

            // ایجاد درخواست پرداخت
            $paymentData = $this->createPaymentRequest($purchase, $dto);

            DB::commit();

            return [
                'purchase_id' => $purchase->id,
                'status' => 'pending',
                'amount' => $finalAmount,
                'payment_url' => $paymentData['payment_url'],
                'authority' => $paymentData['authority'],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * شروع خرید اشتراک
     */
    public function initiateSubscription(PurchaseSubscriptionDTO $dto): array
    {
        DB::beginTransaction();

        try {
            // بررسی طرح اشتراک
            $plan = SubscriptionPlan::with('category')
                ->where('id', $dto->subscriptionPlanId)
                ->where('is_active', true)
                ->firstOrFail();


// بررسی اشتراک فعال
            $existingSub = UserSubscription::where('user_id', $dto->userId)
                ->where('category_id', $plan->category_id)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if ($existingSub) {
                throw new \Exception('شما یک اشتراک فعال برای این دسته‌بندی دارید', 400);
            }

            // محاسبه قیمت
            $amount = $plan->price;
            $discountAmount = $amount * ($plan->discount_percentage / 100);
            $finalAmount = $amount - $discountAmount;

            // اعمال کوپن
            $couponId = null;
            if ($dto->couponCode) {
                $coupon = $this->validateAndApplyCoupon($dto->couponCode, $dto->userId, $finalAmount);
                $additionalDiscount = $this->calculateCouponDiscount($coupon, $finalAmount);
                $discountAmount += $additionalDiscount;
                $finalAmount -= $additionalDiscount;
                $couponId = $coupon->id;
            }

            // ایجاد رکورد خرید
            $purchase = Purchase::create([
                'user_id' => $dto->userId,
                'purchase_type' => 'subscription',
                'book_id' => null,
                'subscription_plan_id' => $plan->id,
                'transaction_id' => $this->generateTransactionId(),
                'amount' => $amount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'tax_amount' => 0,
                'status' => 'pending',
                'coupon_id' => $couponId,
                'coupon_code' => $dto->couponCode,
            ]);

            // ایجاد درخواست پرداخت
            $paymentData = $this->createPaymentRequest($purchase, $dto);

            DB::commit();

            return [
                'purchase_id' => $purchase->id,
                'status' => 'pending',
                'amount' => $finalAmount,
                'plan' => [
                    'duration_months' => $plan->duration_months,
                    'category' => $plan->category->name,
                ],
                'payment_url' => $paymentData['payment_url'],
                'authority' => $paymentData['authority'],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * تکمیل خرید بعد از پرداخت موفق
     */
    public function completePurchase(int $purchaseId, ?string $paymentGatewayRef): bool
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($purchaseId);

            if ($purchase->status === 'completed') {
                return true; // قبلاً تکمیل شده
            }

            // بروزرسانی وضعیت خرید
            $purchase->update([
                'status' => 'completed',
                'purchased_at' => now(),
                'payment_gateway_ref' => $paymentGatewayRef,
            ]);

            if ($purchase->purchase_type === 'book') {
                // اضافه کردن به کتابخانه
                UserLibrary::create([
                    'user_id' => $purchase->user_id,
                    'book_id' => $purchase->book_id,
                    'access_type' => 'purchased',
                    'purchase_id' => $purchase->id,
                    'status' => 'not_started',
                ]);

                // افزایش شمارنده خرید کتاب
                Book::where('id', $purchase->book_id)->increment('purchase_count');

            } elseif ($purchase->purchase_type === 'subscription') {
                // ایجاد اشتراک
                $plan = SubscriptionPlan::findOrFail($purchase->subscription_plan_id);

                $subscription = UserSubscription::create([


'user_id' => $purchase->user_id,
                    'category_id' => $plan->category_id,
                    'subscription_plan_id' => $plan->id,
                    'purchase_id' => $purchase->id,
                    'starts_at' => now(),
                    'expires_at' => now()->addMonths($plan->duration_months),
                    'is_active' => true,
                    'amount_paid' => $purchase->final_amount,
                    'discount_applied' => $purchase->discount_amount,
                ]);

                // اضافه کردن همه کتاب‌های دسته به library
                $this->addCategoryBooksToLibrary($purchase->user_id, $plan->category_id, $subscription->id);
            }

            // ثبت استفاده از کوپن
            if ($purchase->coupon_id) {
                CouponUsage::create([
                    'coupon_id' => $purchase->coupon_id,
                    'user_id' => $purchase->user_id,
                    'purchase_id' => $purchase->id,
                    'discount_amount' => $purchase->discount_amount,
                ]);

                Coupon::where('id', $purchase->coupon_id)->increment('used_count');
            }

            DB::commit();

            // پاک کردن cache
            $this->clearUserCache($purchase->user_id);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * اعتبارسنجی کوپن
     */
    private function validateAndApplyCoupon(string $code, int $userId, float $amount): Coupon
    {
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            throw new \Exception('کد تخفیف نامعتبر است', 400);
        }

        // بررسی تاریخ
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new \Exception('این کد تخفیف هنوز فعال نشده است', 400);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw new \Exception('این کد تخفیف منقضی شده است', 400);
        }

        // بررسی محدودیت کل
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw new \Exception('ظرفیت استفاده از این کد تخفیف تمام شده است', 400);
        }

        // بررسی محدودیت هر کاربر
        $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->count();

        if ($userUsageCount >= $coupon->usage_per_user) {
            throw new \Exception('شما قبلاً از این کد تخفیف استفاده کرده‌اید', 400);
        }

        // بررسی حداقل خرید
        if ($amount < $coupon->min_purchase) {
            throw new \Exception("حداقل مبلغ خرید برای این کد {$coupon->min_purchase} تومان است", 400);
        }

        return $coupon;
    }

    /**
     * محاسبه میزان تخفیف کوپن
     */
    private function calculateCouponDiscount(Coupon $coupon, float $amount): float
    {
        if ($coupon->type === 'percentage') {
            $discount = $amount * ($coupon->value / 100);

            // حداکثر تخفیف
            if ($coupon->max_discount && $discount > $coupon->max_discount) {
                $discount = $coupon->max_discount;
            }

            return $discount;
        }

        // Fixed amount
        return min($coupon->value, $amount);
    }

    /**
     * اضافه کردن کتاب‌های دسته به library
     */
    private function addCategoryBooksToLibrary(int $userId, int $categoryId, int $subscriptionId): void
    {
        $books = Book::where('primary_category_id', $categoryId)
            ->orWhereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
            ->where('status', 'published')
            ->get();

foreach ($books as $book) {
    UserLibrary::updateOrCreate(
        [
            'user_id' => $userId,
            'book_id' => $book->id,
        ],
        [
            'access_type' => 'subscription',
            'subscription_id' => $subscriptionId,
            'access_expires_at' => UserSubscription::find($subscriptionId)->expires_at,
            'status' => 'not_started',
        ]
    );
}
    }

    /**
     * تولید شناسه تراکنش یکتا
     */
    private function generateTransactionId(): string
    {
        return 'TXN-' . strtoupper(Str::random(16));
    }

    /**
     * ایجاد درخواست پرداخت (ZarinPal/IDPay)
     */
    private function createPaymentRequest(Purchase $purchase, $dto): array
    {
        // این قسمت بسته به درگاه پرداخت شما متفاوته
        // مثال با ZarinPal:

        $merchantId = config('payment.zarinpal.merchant_id');
        $amount = $purchase->final_amount;
        $description = $purchase->purchase_type === 'book'
            ? "خرید کتاب - #{$purchase->book_id}"
            : "خرید اشتراک - #{$purchase->subscription_plan_id}";
        $callbackUrl = route('api.payment.callback');

        // فراخوانی API درگاه
        // این یک Mock است - باید با API واقعی جایگزین شود
        $authority = 'A' . strtoupper(Str::random(35));

        $purchase->update([
            'payment_details' => json_encode([
                'authority' => $authority,
                'merchant_id' => $merchantId,
                'device_name' => $dto->deviceName,
            ])
        ]);

        return [
            'payment_url' => "https://www.zarinpal.com/pg/StartPay/{$authority}",
            'authority' => $authority,
        ];
    }

    /**
     * پاک کردن cache کاربر
     */
    private function clearUserCache(int $userId): void
    {
        cache()->forget("user:library:{$userId}");
        cache()->forget("user:subscriptions:{$userId}");
    }
}
