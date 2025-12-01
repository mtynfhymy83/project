<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Authentication Routes (Public)
    Route::prefix('auth')->group(function () {
        // احراز هویت با Eitaa
        Route::post('/eitaa', [AuthController::class, 'eitaaAuth']);

        // تازه‌سازی توکن
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Protected Routes (نیاز به Authentication)
    Route::middleware('auth:api')->group(function () {

        Route::prefix('auth')->group(function () {
            // دریافت اطلاعات کاربر
            Route::get('/me', [AuthController::class, 'me']);

            // خروج
            Route::post('/logout', [AuthController::class, 'logout']);

            // خروج از همه دستگاه‌ها
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        });

        // سایر Route های محافظت شده...

    });
});
