<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\SearchController;

Route::prefix('v1')->group(function () {

    // ============================================
    // Authentication Routes (Public)
    // ============================================
    Route::prefix('auth')->group(function () {
        Route::post('/eitaa', [AuthController::class, 'eitaaAuth']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // ============================================
    // Public Routes
    // ============================================

    // Books
    Route::prefix('books')->group(function () {
        Route::get('/', [BookController::class, 'index']);
        Route::post('/detail', [BookController::class, 'detail']);
        Route::get('/{bookId}/index', [BookController::class, 'getIndex']);
        Route::get('/{bookId}/related', [SearchController::class, 'relatedBooks']);
    });

    // Search (Public)
    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'globalSearch']);
        Route::get('/books', [SearchController::class, 'searchBooks']);
        Route::get('/suggestions', [SearchController::class, 'suggestions']);
        Route::get('/popular', [SearchController::class, 'popularSearches']);
    });

    // ============================================
    // Protected Routes (Need Authentication)
    // ============================================
    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        });

        // Reading Content
        Route::prefix('books')->group(function () {
            Route::post('/read', [BookController::class, 'readPage']);
        });

        // Purchase
        Route::prefix('purchase')->group(function () {
            Route::post('/book', [PurchaseController::class, 'purchaseBook']);
            Route::post('/subscription', [PurchaseController::class, 'purchaseSubscription']);
            Route::get('/my-purchases', [PurchaseController::class, 'myPurchases']);
        });

        // Payment Callback (can be public but we verify purchase ownership)
        Route::post('/payment/callback', [PurchaseController::class, 'paymentCallback']);

        // Library
        Route::prefix('library')->group(function () {
            Route::get('/', [LibraryController::class, 'index']);
            Route::post('/progress', [LibraryController::class, 'updateProgress']);
            Route::post('/session/start', [LibraryController::class, 'startSession']);
            Route::post('/session/end', [LibraryController::class, 'endSession']);
            Route::get('/stats', [LibraryController::class, 'stats']);
            Route::get('/activity', [LibraryController::class, 'recentActivity']);

            // Bookmarks
            Route::get('/bookmarks/{bookId}', [LibraryController::class, 'bookmarks']);
            Route::post('/bookmarks', [LibraryController::class, 'addBookmark']);
            Route::delete('/bookmarks/{bookmarkId}', [LibraryController::class, 'deleteBookmark']);
        });

        // Search in Book Content
        Route::get('/books/{bookId}/search', [SearchController::class, 'searchInBook']);

        // ============================================
        // Admin Routes
        // ============================================
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::delete('/books/{bookId}/cache', [BookController::class, 'clearCache']);
        });
    });
});
