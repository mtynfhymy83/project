<?php

namespace App\DTOs\Purchase;

class PurchaseBookDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId,
        public readonly ?string $couponCode = null,
        public readonly ?string $deviceName = null,
        public readonly ?string $ipAddress = null,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            bookId: (int) $data['book_id'],
            couponCode: $data['coupon_code'] ?? null,
            deviceName: $data['device_name'] ?? null,
            ipAddress: request()->ip(),
        );
    }
}

class PurchaseSubscriptionDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $subscriptionPlanId,
        public readonly ?string $couponCode = null,
        public readonly ?string $deviceName = null,
        public readonly ?string $ipAddress = null,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            subscriptionPlanId: (int) $data['subscription_plan_id'],
            couponCode: $data['coupon_code'] ?? null,
            deviceName: $data['device_name'] ?? null,
            ipAddress: request()->ip(),
        );
    }
}

class PaymentCallbackDTO
{
    public function __construct(
        public readonly string $authority,
        public readonly string $status,
        public readonly ?string $transactionId = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            authority: $data['Authority'] ?? $data['authority'],
            status: $data['Status'] ?? $data['status'],
            transactionId: $data['transaction_id'] ?? null,
        );
    }
}

class VerifyPurchaseDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $purchaseId,
    ) {}
}
