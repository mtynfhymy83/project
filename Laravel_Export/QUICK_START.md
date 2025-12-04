# 🚀 راهنمای سریع شروع

این یک راهنمای خلاصه و سریع برای شروع کار است. برای جزئیات بیشتر به [README.md](README.md) مراجعه کنید.

---

## مرحله 1: کپی فایل‌ها (5 دقیقه)

```bash
# 1. فایل‌های Static
cp -r [CodeIgniter]/style [Laravel]/public/
cp -r [CodeIgniter]/js [Laravel]/public/

# 2. Controllers
cp -r Laravel_Export/app/Http/Controllers/Admin [Laravel]/app/Http/Controllers/

# 3. Middleware
cp Laravel_Export/app/Http/Middleware/AdminMiddleware.php [Laravel]/app/Http/Middleware/

# 4. Helpers
mkdir -p [Laravel]/app/Helpers
cp Laravel_Export/app/Helpers/AdminHelper.php [Laravel]/app/Helpers/

# 5. Views
cp -r Laravel_Export/resources/views/admin [Laravel]/resources/views/

# 6. Routes
cp Laravel_Export/routes/admin.php [Laravel]/routes/

# 7. Config
cp Laravel_Export/config/admin.php [Laravel]/config/

# 8. JS Adapter
cp Laravel_Export/public/js/_admin/laravel-adapter.js [Laravel]/public/js/_admin/
```

---

## مرحله 2: تنظیمات Laravel (5 دقیقه)

### composer.json

```json
{
    "autoload": {
        "files": [
            "app/Helpers/AdminHelper.php"
        ]
    }
}
```

```bash
composer dump-autoload
```

### app/Http/Kernel.php

```php
protected $routeMiddleware = [
    // ...
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
];
```

### routes/web.php

```php
// انتهای فایل
require __DIR__.'/admin.php';
```

---

## مرحله 3: دیتابیس (5 دقیقه)

### Migration

```bash
php artisan make:migration add_admin_fields_to_users_table
```

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

```bash
php artisan migrate
```

### ایجاد ادمین

```bash
php artisan tinker
```

```php
$user = new App\Models\User;
$user->name = 'Admin';
$user->username = 'admin';
$user->email = 'admin@example.com';
$user->password = bcrypt('password');
$user->displayname = 'مدیر سیستم';
$user->level = 'super_admin';
$user->active = 1;
$user->save();
```

---

## مرحله 4: تست (2 دقیقه)

```bash
php artisan serve
```

مرورگر را باز کنید:
- http://localhost:8000/admin
- لاگین با: admin / password

---

## ✅ تمام!

اگر همه چیز کار کرد، باید پنل ادمین را ببینید.

اگر مشکلی بود:
1. بررسی کنید `php artisan route:list | grep admin`
2. بررسی کنید `storage/logs/laravel.log`
3. به [README.md](README.md) بخش "رفع مشکلات" مراجعه کنید

---

## 📝 کارهای بعدی

- [ ] کپی بقیه فایل‌های static (uploads, images)
- [ ] ایجاد Model های Post, Category, etc
- [ ] تبدیل بقیه View ها
- [ ] پیاده‌سازی بقیه API های AJAX
- [ ] تست کامل تمام عملکردها

**موفق باشید! 🎉**

