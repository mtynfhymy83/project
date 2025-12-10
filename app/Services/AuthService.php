<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\DTOs\Auth\EitaaAuthDTO;
use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\RefreshTokenDTO;
use App\DTOs\Auth\UserDTO;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\AccessToken;
use App\Models\RefreshToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    private const ACCESS_TOKEN_TTL = 60; // 60 دقیقه
    private const REFRESH_TOKEN_TTL = 43200; // 30 روز (به دقیقه)

    /**
     * احراز هویت با Eitaa Mini App
     */
    public function authenticateWithEitaa(EitaaAuthDTO $dto): AuthResponseDTO
    {
        try {
            DB::beginTransaction();

            // 1. بررسی Bot Token
            $botToken = config('services.eitaa.bot_token');

            if (empty($botToken)) {
                return new AuthResponseDTO(
                    success: false,
                    message: 'تنظیمات ایتا ناقص است. لطفاً با پشتیبانی تماس بگیرید'
                );
            }

            // 2. اعتبارسنجی داده‌های Eitaa
            if (!$this->validateEitaaData($dto->eitaaData, $botToken)) {
                return new AuthResponseDTO(
                    success: false,
                    message: 'داده‌های ایتا معتبر نیست'
                );
            }

            // 2. استخراج اطلاعات کاربر
            $eitaaUserData = $this->parseEitaaData($dto->eitaaData);

            // 3. جستجوی کاربر با Eitaa ID (با eager loading برای جلوگیری از N+1)
            $userProfile = UserProfile::with('user')->where('eitaa_id', $eitaaUserData['id'])->first();

            $isNewUser = false;

            if ($userProfile) {
                // کاربر موجود است
                $user = $userProfile->user;
            } else {
                // ثبت‌نام کاربر جدید
                $user = $this->registerNewUser($eitaaUserData);
                $isNewUser = true;
            }

            // 4. بررسی وضعیت کاربر
            if (!$user->isActive()) {
                DB::rollBack();
                return new AuthResponseDTO(
                    success: false,
                    message: 'حساب کاربری شما غیرفعال است'
                );
            }

            // 5. تولید توکن‌ها
            $tokens = $this->generateTokens($user, [
                'device_name' => $dto->deviceName,
                'device_type' => $dto->deviceType,
                'platform' => $dto->platform,
                'ip_address' => $dto->ipAddress,
                'user_agent' => $dto->userAgent,
            ]);

            // 6. بروزرسانی آخرین ورود
            $user->updateLastLogin();

            DB::commit();

            return new AuthResponseDTO(
                success: true,
                accessToken: $tokens['access_token'],
                refreshToken: $tokens['refresh_token'],
                tokenType: 'Bearer',
                expiresIn: $tokens['expires_in'],
                expiresAt: $tokens['expires_at'],
                user: UserDTO::fromModel($user)->toArray(),
                message: $isNewUser ? 'ثبت‌نام با موفقیت انجام شد' : 'ورود با موفقیت انجام شد',
                isNewUser: $isNewUser
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return new AuthResponseDTO(
                success: false,
                message: 'خطا در احراز هویت: ' . $e->getMessage()
            );
        }
    }

    /**
     * ثبت‌نام کاربر جدید
     */
    private function registerNewUser(array $eitaaUserData): User
    {
        $username = $eitaaUserData['username'] ?? 'user_' . $eitaaUserData['id'];
        $firstName = $eitaaUserData['first_name'] ?? '';
        $lastName = $eitaaUserData['last_name'] ?? '';
        $email = $eitaaUserData['email'] ?? $username . '@eitaa.local';



        $user = User::create([
            'name' => trim($firstName . ' ' . $lastName) ?: $username,
            'email' => $email,
            'tel' => null,
            'password' => Hash::make(uniqid()), // پسورد رندوم
            'approved' => '1',
        ]);

        // ایجاد UserProfile
        UserProfile::create([
            'user_id' => $user->id,
            'eitaa_id' => $eitaaUserData['id'],
            'username' => $username,
            'name' => $firstName,
            'family' => $lastName,
        ]);

        return $user->fresh(['userProfile']);
    }

    /**
     * تولید Access Token و Refresh Token
     *
     * Access Token: JWT stateless (ذخیره نمی‌شود در دیتابیس)
     * Refresh Token: ذخیره می‌شود در دیتابیس برای امنیت بیشتر
     */


    public function generateTokens(User $user, array $deviceInfo = []): array
    {
        // تولید JWT Access Token (stateless - بدون ذخیره در دیتابیس)
        $accessToken = JWTAuth::fromUser($user);
        $expiresAt = Carbon::now()->addMinutes(self::ACCESS_TOKEN_TTL);

        // تولید Refresh Token (ذخیره می‌شود در دیتابیس)
        $refreshTokenModel = RefreshToken::create([
            'user_id' => $user->id,
            'access_token_id' => null, // دیگر نیازی به access_token_id نیست
            'expires_at' => Carbon::now()->addMinutes(self::REFRESH_TOKEN_TTL),
            'device_name' => $deviceInfo['device_name'] ?? null,
            'ip_address' => $deviceInfo['ip_address'] ?? null,
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenModel->token,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL * 60, // به ثانیه
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * تازه‌سازی Access Token
     */
    public function refreshAccessToken(RefreshTokenDTO $dto): AuthResponseDTO
    {
        try {
            DB::beginTransaction();

            // 1. یافتن Refresh Token (با eager loading)
            $refreshToken = RefreshToken::with(['user', 'accessToken'])
                ->where('token', $dto->refreshToken)
                ->first();

            if (!$refreshToken) {
                return new AuthResponseDTO(
                    success: false,
                    message: 'توکن تازه‌سازی نامعتبر است'
                );
            }

            // 2. بررسی اعتبار
            if (!$refreshToken->isValid()) {
                return new AuthResponseDTO(
                    success: false,
                    message: 'توکن تازه‌سازی منقضی شده یا استفاده شده است'
                );
            }

            // 3. علامت‌گذاری به عنوان استفاده شده
            $refreshToken->markAsUsed();

            // 4. تولید توکن‌های جدید
            // (Access Token قبلی به صورت خودکار با JWT expiration منقضی می‌شود)
            $user = $refreshToken->user;
            $tokens = $this->generateTokens($user, [
                'device_name' => $dto->deviceName,
                'ip_address' => $dto->ipAddress,
            ]);

            DB::commit();

            return new AuthResponseDTO(
                success: true,
                accessToken: $tokens['access_token'],
                refreshToken: $tokens['refresh_token'],
                tokenType: 'Bearer',
                expiresIn: $tokens['expires_in'],
                expiresAt: $tokens['expires_at'],
                user: UserDTO::fromModel($user)->toArray(),
                message: 'توکن با موفقیت تازه‌سازی شد'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return new AuthResponseDTO(
                success: false,
                message: 'خطا در تازه‌سازی توکن: ' . $e->getMessage()
            );
        }
    }

    /**
     * Decrypt encrypted data (در صورت نیاز)
     *
     * @param string $encryptedData
     * @param string $iv
     * @param string $secretKey
     * @return array|null
     */
    private function decryptEitaaData(string $encryptedData, string $iv, string $secretKey): ?array
    {
        // بررسی معتبر بودن ورودی‌ها
        if (empty($encryptedData) || empty($iv) || empty($secretKey)) {
            return null;
        }

        // تبدیل secret key و IV به فرمت باینری
        $secretKey = hex2bin($secretKey);
        $iv = hex2bin($iv);

        // رمزگشایی با AES-256-CBC
        $decryptedData = openssl_decrypt(
            $encryptedData,
            'aes-256-cbc',
            $secretKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decryptedData ? json_decode($decryptedData, true) : null;
    }

    /**
     * خروج کاربر
     *
     * استفاده از JWT Blacklist برای لغو توکن (stateless)
     */
    public function logout(?string $accessToken = null): bool
    {
        try {
            // استفاده از JWT Blacklist برای لغو توکن
            // این کار توکن را در blacklist قرار می‌دهد (در cache/redis)
            // بدون نیاز به ذخیره در دیتابیس
            if ($accessToken) {
                JWTAuth::setToken($accessToken)->invalidate();
            } else {
                JWTAuth::invalidate(JWTAuth::getToken());
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * خروج از همه دستگاه‌ها
     *
     * فقط Refresh Tokens را لغو می‌کنیم
     * Access Tokens به صورت خودکار با expiration منقضی می‌شوند
     */
    public function logoutFromAllDevices(int $userId): bool
    {
        try {
            DB::beginTransaction();

            // لغو تمام Refresh Tokens (Access Tokens stateless هستند)
            RefreshToken::where('user_id', $userId)
                ->where('is_revoked', false)
                ->update([
                    'is_revoked' => true,
                    'revoked_at' => now()
                ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * اعتبارسنجی Eitaa Data با الگوریتم امنیتی Eitaa
     */
    public function validateEitaaData(string $eitaaData, string $botToken): bool
    {
        // بررسی ورودی‌ها
        if (empty($eitaaData) || empty($botToken)) {
            return false;
        }

        parse_str($eitaaData, $params);

        if (!isset($params['hash'])) {
            return false;
        }

        $receivedHash = $params['hash'];
        unset($params['hash']);

        // مرتب‌سازی پارامترها
        ksort($params);

        // ساخت Data Check String
        $dataCheckArray = [];
        foreach ($params as $key => $value) {
            $dataCheckArray[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $dataCheckArray);

        // محاسبه Secret Key با استفاده از "WebAppData" (مهم!)
        $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);

        // محاسبه Hash
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * استخراج اطلاعات از Eitaa Data
     */
    public function parseEitaaData(string $eitaaData): array
    {
        parse_str($eitaaData, $params);

        $userData = json_decode($params['user'] ?? '{}', true);

        return [
            'id' => (string)($userData['id'] ?? ''),
            'first_name' => $userData['first_name'] ?? '',
            'last_name' => $userData['last_name'] ?? '',
            'username' => $userData['username'] ?? null,
            'email' => $userData['email'] ?? null,
            'language_code' => $userData['language_code'] ?? 'fa',
            'allows_write_to_pm' => $userData['allows_write_to_pm'] ?? false,
            'auth_date' => $params['auth_date'] ?? null,
            'device_id' => $params['device_id'] ?? null,
            'query_id' => $params['query_id'] ?? null,
        ];
    }
}
