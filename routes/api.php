<?php

use App\Http\Controllers\Api\V1\BookController;
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

    Route::prefix('books')->group(function () {
        // لیست کتاب‌ها (عمومی)
        Route::get('/', [BookController::class, 'index']);

        // جزئیات کتاب (عمومی - اما user_access فقط برای login شده‌ها)
        Route::post('/detail', [BookController::class, 'detail']);

        // فهرست کتاب (عمومی)
        Route::get('/{bookId}/index', [BookController::class, 'getIndex']);
    });

    // Protected Routes (نیاز به Authentication)
    Route::middleware('auth:api')->group(function () {

        Route::prefix('books')->group(function () {
            // خواندن محتوا (نیاز به خرید یا اشتراک)
            Route::post('/read', [BookController::class, 'readPage']);

            // Admin routes
            Route::middleware('admin')->group(function () {
                Route::delete('/{bookId}/cache', [BookController::class, 'clearCache']);
            });
        });

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
