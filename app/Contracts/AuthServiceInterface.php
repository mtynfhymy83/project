<?php

namespace App\Contracts;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\EitaaAuthDTO;
use App\DTOs\Auth\RefreshTokenDTO;
use App\Models\User;

interface AuthServiceInterface
{
    /**
     * احراز هویت و ثبت‌نام خودکار با Eitaa
     */
    public function authenticateWithEitaa(EitaaAuthDTO $dto): AuthResponseDTO;

    /**
     * تولید Access Token و Refresh Token
     */
    public function generateTokens(User $user, array $deviceInfo = []): array;

    /**
     * تازه‌سازی Access Token با Refresh Token
     */
    public function refreshAccessToken(RefreshTokenDTO $dto): AuthResponseDTO;

    /**
     * خروج کاربر (لغو توکن‌ها)
     */
    public function logout(?string $accessToken = null): bool;

    /**
     * خروج از همه دستگاه‌ها
     */
    public function logoutFromAllDevices(int $userId): bool;

    /**
     * اعتبارسنجی Eitaa Data
     */
    public function validateEitaaData(string $eitaaData, string $botToken): bool;

    /**
     * استخراج اطلاعات از Eitaa Data
     */
    public function parseEitaaData(string $eitaaData): array;
}
