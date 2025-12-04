<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ApiController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| این فایل را در routes/web.php اینکلود کنید:
| require __DIR__.'/admin.php';
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'admin'])  // تنظیم middleware ها
    ->group(function () {
        
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/home', [DashboardController::class, 'index'])->name('home');
        Route::get('/home/statistics', [DashboardController::class, 'statistics'])->name('statistics');
        
        // Users Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/adduser', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{id}', [UserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UserController::class, 'update'])->name('update');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
            Route::get('/levels', [UserController::class, 'levels'])->name('levels');
            Route::get('/chart', [UserController::class, 'chart'])->name('chart');
        });
        
        // Posts Management
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/primary', [PostController::class, 'index'])->name('index');
            Route::get('/', [PostController::class, 'index'])->name('list');
            Route::get('/add', [PostController::class, 'create'])->name('create');
            Route::post('/', [PostController::class, 'store'])->name('store');
            Route::get('/{id}', [PostController::class, 'edit'])->name('edit');
            Route::put('/{id}', [PostController::class, 'update'])->name('update');
            Route::delete('/{id}', [PostController::class, 'destroy'])->name('destroy');
            Route::get('/category', [PostController::class, 'category'])->name('category');
        });
        
        // API Routes for AJAX Calls
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/', [ApiController::class, 'index'])->name('index');
            
            // User API
            Route::post('/getUserInfo/{id}', [ApiController::class, 'getUserInfo']);
            Route::post('/updateUser/{id}', [ApiController::class, 'updateUser']);
            Route::post('/getUserBooks/{id}', [ApiController::class, 'getUserBooks']);
            Route::post('/addUserBooks', [ApiController::class, 'addUserBooks']);
            Route::post('/deleteUserBooks/{id}', [ApiController::class, 'deleteUserBooks']);
            Route::post('/getUserHL/{id}', [ApiController::class, 'getUserHighlights']);
            Route::post('/getUserMobiles/{id}', [ApiController::class, 'getUserMobiles']);
            Route::post('/DeleteUserMobile/{id}', [ApiController::class, 'deleteUserMobile']);
            
            // Membership API
            Route::post('/getDataMembership/{id}', [ApiController::class, 'getDataMembership']);
            Route::post('/getAllMembership/{id}', [ApiController::class, 'getAllMembership']);
            Route::post('/saveAdminMembership/{id}', [ApiController::class, 'saveAdminMembership']);
            Route::post('/deleteAdminMembership', [ApiController::class, 'deleteAdminMembership']);
            
            // General API
            Route::post('/getBooks/{search}', [ApiController::class, 'getBooks']);
            Route::post('/upload', [ApiController::class, 'upload'])->name('upload');
            Route::post('/delete/{table}/{id}', [ApiController::class, 'delete'])->name('delete');
        });
        
        // Comments
        Route::prefix('comments')->name('comments.')->group(function () {
            Route::get('/', 'CommentController@index')->name('index');
            Route::get('/index/{status?}', 'CommentController@index')->name('index.status');
        });
        
        // Geo Section
        Route::prefix('geosection')->name('geosection.')->group(function () {
            Route::get('/', 'GeoSectionController@index')->name('index');
        });
        
        Route::prefix('geotype')->name('geotype.')->group(function () {
            Route::get('/', 'GeoTypeController@index')->name('index');
        });
        
        // Advertise
        Route::prefix('advertise')->name('advertise.')->group(function () {
            Route::get('/', 'AdvertiseController@index')->name('index');
        });
        
        // SMS
        Route::prefix('payamak')->name('payamak.')->group(function () {
            Route::get('/', 'PayamakController@index')->name('index');
        });
        
        // Dictionary
        Route::prefix('dictionary')->name('dictionary.')->group(function () {
            Route::get('/', 'DictionaryController@index')->name('index');
        });
        
        Route::prefix('diclang')->name('diclang.')->group(function () {
            Route::get('/', 'DicLangController@index')->name('index');
        });
        
        // Suppliers
        Route::prefix('supplier')->name('supplier.')->group(function () {
            Route::get('/', 'SupplierController@index')->name('index');
        });
        
        Route::prefix('suppliertype')->name('suppliertype.')->group(function () {
            Route::get('/', 'SupplierTypeController@index')->name('index');
        });
        
        // Categories
        Route::prefix('tecat')->name('tecat.')->group(function () {
            Route::get('/', 'TeCatController@index')->name('index');
        });
        
        Route::prefix('mecat')->name('mecat.')->group(function () {
            Route::get('/', 'MeCatController@index')->name('index');
        });
        
        // Membership
        Route::prefix('membership')->name('membership.')->group(function () {
            Route::get('/', 'MembershipController@index')->name('index');
        });
        
        // Classes
        Route::prefix('classonline')->name('classonline.')->group(function () {
            Route::get('/', 'ClassOnlineController@index')->name('index');
        });
        
        Route::prefix('xlsxclassonline')->name('xlsxclassonline.')->group(function () {
            Route::get('/', 'XlsxClassOnlineController@index')->name('index');
        });
        
        Route::prefix('classroom')->name('classroom.')->group(function () {
            Route::get('/', 'ClassroomController@index')->name('index');
        });
        
        // Courses
        Route::prefix('nezam')->name('nezam.')->group(function () {
            Route::get('/', 'NezamController@index')->name('index');
        });
        
        Route::prefix('doreh')->name('doreh.')->group(function () {
            Route::get('/', 'DorehController@index')->name('index');
        });
        
        Route::prefix('dorehclass')->name('dorehclass.')->group(function () {
            Route::get('/', 'DorehClassController@index')->name('index');
        });
        
        Route::prefix('jalasat')->name('jalasat.')->group(function () {
            Route::get('/', 'JalasatController@index')->name('index');
        });
        
        // Discount
        Route::prefix('discount')->name('discount.')->group(function () {
            Route::get('/', 'DiscountController@index')->name('index');
        });
        
        // Payment
        Route::prefix('payment')->name('payment.')->group(function () {
            Route::get('/', 'PaymentController@index')->name('index');
        });
        
        // Reports
        Route::prefix('gozaresh')->name('gozaresh.')->group(function () {
            Route::get('/', 'GozareshController@index')->name('index');
        });
        
        Route::prefix('salereport')->name('salereport.')->group(function () {
            Route::get('/', 'SaleReportController@index')->name('index');
        });
        
        // Questions / Support
        Route::prefix('questions')->name('questions.')->group(function () {
            Route::get('/', 'QuestionController@index')->name('index');
            Route::get('/editQuestion', 'QuestionController@create')->name('create');
        });
        
        Route::prefix('catquest')->name('catquest.')->group(function () {
            Route::get('/', 'CatQuestController@index')->name('index');
        });
        
        // Azmoon
        Route::prefix('azmoon')->name('azmoon.')->group(function () {
            Route::get('/', 'AzmoonController@index')->name('index');
        });
        
        // Leitner
        Route::prefix('leitner')->name('leitner.')->group(function () {
            Route::get('/', 'LeitnerController@index')->name('index');
        });
        
        // Settings
        Route::prefix('setting')->name('setting.')->group(function () {
            Route::get('/', 'SettingController@index')->name('index');
            Route::post('/', 'SettingController@update')->name('update');
        });
        
        // Logout
        Route::get('/logout', function () {
            auth()->logout();
            return redirect()->route('login');
        })->name('logout');
    });

