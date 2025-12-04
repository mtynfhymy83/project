# راهنمای مهاجرت پنل ادمین از CodeIgniter به Laravel

این پکیج شامل تمام فایل‌های لازم برای مهاجرت پنل ادمین پروژه Madras از CodeIgniter به Laravel است.

## 📋 فهرست مطالب

- [پیش‌نیازها](#پیش-نیازها)
- [ساختار فایل‌ها](#ساختار-فایل-ها)
- [مراحل نصب](#مراحل-نصب)
- [پیکربندی](#پیکربندی)
- [تنظیمات اضافی](#تنظیمات-اضافی)
- [نکات مهم](#نکات-مهم)
- [رفع مشکلات](#رفع-مشکلات)

---

## 🔧 پیش‌نیازها

- PHP >= 8.0
- Laravel >= 9.x
- Composer
- پکیج‌های پیشنهادی:
  ```bash
  composer require hekmatinasser/verta  # برای تاریخ شمسی
  composer require intervention/image   # برای مدیریت تصاویر
  ```

---

## 📁 ساختار فایل‌ها

```
Laravel_Export/
├── app/
│   ├── Http/
│   │   ├── Controllers/Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── UserController.php
│   │   │   ├── PostController.php
│   │   │   └── ApiController.php
│   │   └── Middleware/
│   │       └── AdminMiddleware.php
│   └── Helpers/
│       └── AdminHelper.php
├── resources/views/admin/
│   ├── layouts/
│   │   ├── app.blade.php
│   │   ├── header.blade.php
│   │   ├── sidebar.blade.php
│   │   └── footer.blade.php
│   ├── dashboard/
│   │   └── index.blade.php
│   ├── users/
│   │   └── index.blade.php
│   └── posts/
│       └── index.blade.php
├── routes/
│   └── admin.php
├── public/js/_admin/
│   └── laravel-adapter.js
└── config/
    └── admin.php
```

---

## 🚀 مراحل نصب

### مرحله 1: کپی فایل‌های Static

فایل‌های CSS، JS و تصاویر را از پروژه CodeIgniter کپی کنید:

```bash
# از پروژه CodeIgniter
cp -r style/* [Laravel_Project]/public/style/
cp -r js/* [Laravel_Project]/public/js/
cp -r uploads/* [Laravel_Project]/public/uploads/
```

### مرحله 2: کپی فایل‌های Laravel_Export

تمام محتویات فولدر `Laravel_Export` را به پروژه Laravel کپی کنید:

```bash
# Controllers
cp -r Laravel_Export/app/Http/Controllers/Admin/* [Laravel_Project]/app/Http/Controllers/Admin/

# Middleware
cp Laravel_Export/app/Http/Middleware/AdminMiddleware.php [Laravel_Project]/app/Http/Middleware/

# Helpers
cp Laravel_Export/app/Helpers/AdminHelper.php [Laravel_Project]/app/Helpers/

# Views
cp -r Laravel_Export/resources/views/admin/* [Laravel_Project]/resources/views/admin/

# Routes
cp Laravel_Export/routes/admin.php [Laravel_Project]/routes/

# Config
cp Laravel_Export/config/admin.php [Laravel_Project]/config/

# JS Adapter
cp Laravel_Export/public/js/_admin/laravel-adapter.js [Laravel_Project]/public/js/_admin/
```

### مرحله 3: ثبت Helper در composer.json

فایل `composer.json` پروژه Laravel را ویرایش کنید:

```json
{
    "autoload": {
        "files": [
            "app/Helpers/AdminHelper.php"
        ]
    }
}
```

سپس:

```bash
composer dump-autoload
```

### مرحله 4: ثبت Middleware

فایل `app/Http/Kernel.php` را ویرایش کنید:

```php
protected $routeMiddleware = [
    // ... سایر middleware ها
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
];
```

### مرحله 5: اضافه کردن Route

فایل `routes/web.php` را ویرایش کنید:

```php
// اضافه کردن route های ادمین
require __DIR__.'/admin.php';
```

### مرحله 6: اضافه کردن JS Adapter به Layout

فایل `resources/views/admin/layouts/app.blade.php` را ویرایش کنید و قبل از بستن تگ `</head>` این خط را اضافه کنید:

```blade
<script src="{{ asset('js/_admin/laravel-adapter.js') }}"></script>
```

---

## ⚙️ پیکربندی

### 1. تنظیمات .env

فایل `.env` را ویرایش کنید:

```env
# Admin Panel
ADMIN_TITLE="پنل مدیریت مدرس"
ADMIN_LOGO="/images/logo.png"
ADMIN_FAVICON="/images/favicon.ico"

# SFTP (اختیاری)
SFTP_URL=https://louhnyrh.lexoyacloud.ir
SFTP_HOST=your-sftp-host
SFTP_PORT=22
SFTP_USERNAME=your-username
SFTP_PASSWORD=your-password
```

### 2. مدل User

مطمئن شوید مدل `User` شما فیلدهای زیر را دارد:

```php
protected $fillable = [
    'username',
    'email',
    'password',
    'displayname',
    'tel',
    'level',
    'active',
    'support',
    'avatar',
    // ... سایر فیلدها
];

protected $hidden = [
    'password',
    'remember_token',
];
```

### 3. مدل‌های دیگر

برای کارکرد کامل، باید مدل‌های زیر را ایجاد کنید:

- `Post`
- `Category`
- `Comment`
- `Payment`
- `Book`
- `Membership`

نمونه مدل Post:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'thumbnail',
        'category_id',
        'author_id',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getThumbnailUrlAttribute()
    {
        return get_thumbnail($this->thumbnail);
    }
}
```

### 4. Migration ها

ایجاد migration های لازم:

```bash
php artisan make:migration create_posts_table
php artisan make:migration create_categories_table
php artisan make:migration add_admin_fields_to_users_table
```

نمونه migration برای فیلدهای ادمین در جدول users:

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('username')->unique()->after('id');
        $table->string('displayname')->after('name');
        $table->string('tel')->nullable()->after('email');
        $table->string('level')->default('user')->after('password');
        $table->boolean('active')->default(1)->after('level');
        $table->boolean('support')->default(0)->after('active');
        $table->string('avatar')->nullable()->after('support');
    });
}
```

---

## 🔐 سیستم دسترسی (Permissions)

### روش 1: استفاده ساده از Level

در مدل User:

```php
public function can($permission)
{
    // Super Admin دسترسی به همه چیز دارد
    if ($this->level === 'super_admin') {
        return true;
    }
    
    // Admin دسترسی به اکثر موارد دارد
    if ($this->level === 'admin') {
        return true;
    }
    
    // بررسی دسترسی خاص
    // این را باید بر اساس جدول permissions پیاده‌سازی کنید
    return false;
}
```

### روش 2: استفاده از Package (پیشنهادی)

```bash
composer require spatie/laravel-permission
```

سپس به مستندات پکیج مراجعه کنید:
https://spatie.be/docs/laravel-permission

---

## 📝 تنظیمات اضافی

### 1. Storage Link

برای دسترسی به فایل‌های آپلود شده:

```bash
php artisan storage:link
```

### 2. Cache Config

بعد از تغییر فایل‌های config:

```bash
php artisan config:cache
```

### 3. پاکسازی View Cache

```bash
php artisan view:clear
```

### 4. ایجاد کاربر ادمین اولیه

```bash
php artisan tinker
```

```php
$user = new App\Models\User;
$user->username = 'admin';
$user->email = 'admin@example.com';
$user->password = bcrypt('password');
$user->displayname = 'مدیر سیستم';
$user->level = 'super_admin';
$user->active = 1;
$user->save();
```

---

## ⚠️ نکات مهم

### 1. CSRF Token

تمام فرم‌ها باید `@csrf` داشته باشند:

```blade
<form method="POST">
    @csrf
    <!-- ... -->
</form>
```

### 2. AJAX Requests

فایل `laravel-adapter.js` به صورت خودکار CSRF token را به همه درخواست‌های AJAX اضافه می‌کند.

### 3. Route Names

از route names استفاده کنید نه URL های خام:

```blade
❌ <a href="/admin/users">کاربران</a>
✅ <a href="{{ route('admin.users.index') }}">کاربران</a>
```

### 4. Asset URLs

برای فایل‌های static از `asset()` استفاده کنید:

```blade
❌ <img src="/images/logo.png">
✅ <img src="{{ asset('images/logo.png') }}">
```

### 5. Old Input Values

برای حفظ مقادیر فرم بعد از خطا:

```blade
<input type="text" name="title" value="{{ old('title', $post->title ?? '') }}">
```

### 6. Validation Errors

نمایش خطاهای validation:

```blade
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

---

## 🐛 رفع مشکلات

### مشکل 1: 404 Not Found

**علت:** Route ها ثبت نشده‌اند  
**راه حل:**

```bash
php artisan route:clear
php artisan route:cache
```

### مشکل 2: Class Not Found

**علت:** Autoload به‌روز نیست  
**راه حل:**

```bash
composer dump-autoload
```

### مشکل 3: 419 Page Expired (CSRF)

**علت:** CSRF token اشتباه یا منقضی شده  
**راه حل:**
- مطمئن شوید `laravel-adapter.js` لود شده
- در layout مطمئن شوید `<meta name="csrf-token">` وجود دارد
- Cache مرورگر را پاک کنید

### مشکل 4: تصاویر نمایش داده نمی‌شوند

**علت:** Storage link وجود ندارد  
**راه حل:**

```bash
php artisan storage:link
```

### مشکل 5: خطای Permission Denied

**علت:** Middleware یا سیستم دسترسی به درستی پیکربندی نشده  
**راه حل:**
- بررسی `AdminMiddleware`
- بررسی متد `can()` در مدل User
- بررسی مقدار `level` کاربر

---

## 📚 منابع مفید

- [مستندات Laravel](https://laravel.com/docs)
- [Laravel Blade Templates](https://laravel.com/docs/blade)
- [Laravel Routing](https://laravel.com/docs/routing)
- [Laravel Middleware](https://laravel.com/docs/middleware)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)

---

## ✅ چک‌لیست مهاجرت

- [ ] کپی فایل‌های static (CSS, JS, Images)
- [ ] کپی فایل‌های Controller
- [ ] کپی فایل‌های View
- [ ] کپی و ثبت Middleware
- [ ] کپی و ثبت Helper
- [ ] اضافه کردن Routes
- [ ] ایجاد/تغییر Migration ها
- [ ] اجرای Migration ها
- [ ] ایجاد Models
- [ ] تنظیم .env
- [ ] ایجاد Storage Link
- [ ] ایجاد کاربر ادمین اولیه
- [ ] تست صفحات اصلی (Dashboard, Users, Posts)
- [ ] تست عملکرد AJAX
- [ ] تست آپلود فایل
- [ ] تست سیستم دسترسی

---

## 🎯 مراحل بعدی

1. **تبدیل View های باقی‌مانده:** فقط view های اصلی (Dashboard, Users, Posts) تبدیل شده‌اند. سایر view ها را به تدریج تبدیل کنید.

2. **پیاده‌سازی API های باقی‌مانده:** در `ApiController` فقط متدهای پایه پیاده‌سازی شده‌اند.

3. **بهینه‌سازی:** 
   - اضافه کردن Cache
   - اضافه کردن Queue برای پردازش‌های سنگین
   - بهینه‌سازی Query ها با Eager Loading

4. **امنیت:**
   - اضافه کردن Rate Limiting
   - اضافه کردن Two-Factor Authentication
   - بررسی و تست امنیتی

5. **تست:**
   - نوشتن Unit Tests
   - نوشتن Feature Tests

---

## 📞 پشتیبانی

در صورت بروز مشکل، موارد زیر را بررسی کنید:
1. Log های Laravel در `storage/logs/laravel.log`
2. Browser Console برای خطاهای JavaScript
3. Network Tab در DevTools برای خطاهای AJAX

---

**موفق باشید! 🚀**

