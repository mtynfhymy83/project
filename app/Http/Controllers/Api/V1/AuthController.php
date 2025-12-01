<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EitaaAuthRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Contracts\AuthServiceInterface;
use App\DTOs\Auth\EitaaAuthDTO;
use App\DTOs\Auth\RefreshTokenDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {}

    /**
     * احراز هویت با Eitaa Mini App
     *
     * @param EitaaAuthRequest $request
     * @return JsonResponse
     */
    public function eitaaAuth(EitaaAuthRequest $request): JsonResponse
    {
        try {
            $validated = $request->validatedWithCleaning();

            $dto = EitaaAuthDTO::fromRequest($validated);
            $response = $this->authService->authenticateWithEitaa($dto);

            $statusCode = $response->success ? 200 : 401;

            return response()->json($response->toArray(), $statusCode);

        } catch (\Exception $e) {
            Log::error('Eitaa Auth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطای سرور در احراز هویت'
            ], 500);
        }
    }

    /**
     * تازه‌سازی Access Token
     *
     * @param RefreshTokenRequest $request
     * @return JsonResponse
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $dto = RefreshTokenDTO::fromRequest($request->validated());
            $response = $this->authService->refreshAccessToken($dto);

            $statusCode = $response->success ? 200 : 401;

            return response()->json($response->toArray(), $statusCode);

        } catch (\Exception $e) {
            Log::error('Token Refresh Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطای سرور در تازه‌سازی توکن'
            ], 500);
        }
    }

    /**
     * خروج از حساب کاربری
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $token = request()->bearerToken();
            $result = $this->authService->logout($token);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'خروج با موفقیت انجام شد'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'خطا در خروج از حساب'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Logout Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطای سرور در خروج'
            ], 500);
        }
    }

    /**
     * خروج از همه دستگاه‌ها
     *
     * @return JsonResponse
     */
    public function logoutAll(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است'
                ], 401);
            }

            $result = $this->authService->logoutFromAllDevices($user->id);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'خروج از همه دستگاه‌ها با موفقیت انجام شد'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'خطا در خروج از دستگاه‌ها'
            ], 400);

} catch (\Exception $e) {
            Log::error('Logout All Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطای سرور'
            ], 500);
        }
    }

    /**
     * دریافت اطلاعات کاربر جاری
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'display_name' => $user->display_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'created_at' => $user->created_at?->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get User Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطای سرور'
            ], 500);
        }
    }
}
