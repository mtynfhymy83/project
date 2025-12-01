<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Stateless JWT validation - no database queries needed!
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // دریافت توکن از هدر
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن دسترسی یافت نشد'
                ], 401);
            }

            // اعتبارسنجی JWT (stateless - بدون نیاز به دیتابیس)
            // JWT خودش expiration و signature را بررسی می‌کند
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر یافت نشد'
                ], 401);
            }

            // بررسی وضعیت کاربر
            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حساب کاربری غیرفعال است'
                ], 403);
            }

            // اضافه کردن کاربر به request
            $request->setUserResolver(fn() => $user);

            return $next($request);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'توکن منقضی شده است'
            ], 401);
        } catch (TokenBlacklistedException $e) {
            return response()->json([
                'success' => false,
                'message' => 'توکن لغو شده است'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'توکن نامعتبر است'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در احراز هویت'
            ], 500);
        }
    }
}
