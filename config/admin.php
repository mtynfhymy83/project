<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Admin Panel Settings
    |--------------------------------------------------------------------------
    |
    | تنظیمات مربوط به پنل مدیریت
    |
    */
    
    'title' => env('ADMIN_TITLE', 'پنل مدیریت'),
    
    'logo' => env('ADMIN_LOGO', '/images/logo.png'),
    
    'favicon' => env('ADMIN_FAVICON', '/images/favicon.ico'),
    
    /*
    |--------------------------------------------------------------------------
    | Admin Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware هایی که باید برای پنل ادمین اعمال شوند
    |
    */
    
    'middleware' => [
        'web',
        'auth',
        'admin',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Admin Permissions
    |--------------------------------------------------------------------------
    |
    | لیست دسترسی‌های پنل ادمین
    |
    */
    
    'permissions' => [
        // Users
        'manage_users' => 'مدیریت کاربران',
        'create_user' => 'ایجاد کاربر',
        'edit_user' => 'ویرایش کاربر',
        'delete_user' => 'حذف کاربر',
        'edit_user_role' => 'تغییر نقش کاربر',
        'edit_user_levels' => 'مدیریت سطوح دسترسی',
        
        // Posts
        'read_post' => 'مشاهده پست‌ها',
        'creat_post' => 'ایجاد پست',
        'edit_post' => 'ویرایش پست',
        'delete_post' => 'حذف پست',
        'category_post' => 'مدیریت دسته‌بندی پست‌ها',
        
        // Comments
        'read_comment' => 'مشاهده نظرات',
        'edit_comment' => 'ویرایش نظر',
        'delete_comment' => 'حذف نظر',
        
        // Settings
        'change_settings' => 'تغییر تنظیمات',
        
        // Payments
        'manage_payment' => 'مدیریت پرداخت‌ها',
        'manage_discount' => 'مدیریت کد تخفیف',
        
        // Reports
        'manage_gozaresh' => 'مشاهده گزارش مالی',
        'manage_salereport' => 'مشاهده گزارش فروش',
        'site_visits' => 'مشاهده آمار بازدید',
        
        // Support
        'manage_questions' => 'مدیریت پشتیبانی',
        'manage_catquest' => 'مدیریت دسته‌بندی پشتیبانی',
        
        // Others
        'manage_advertise' => 'مدیریت تبلیغات',
        'manage_payamak' => 'ارسال پیامک',
        'manage_dictionary' => 'مدیریت لغتنامه',
        'manage_diclang' => 'مدیریت زبان‌های ترجمه',
        'manage_supplier' => 'مدیریت عرضه‌کنندگان',
        'manage_suppliertype' => 'مدیریت نوع عرضه‌کننده',
        'is_supplier' => 'دسترسی عرضه‌کننده',
        'manage_geosection' => 'مدیریت بخش جغرافیایی',
        'manage_geotype' => 'مدیریت نوع مناطق',
        'manage_azmoon' => 'مدیریت آزمون',
        'leitner' => 'دسترسی به جعبه لایتنر',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | تعداد آیتم‌ها در هر صفحه
    |
    */
    
    'pagination' => [
        'users' => 60,
        'posts' => 30,
        'comments' => 50,
        'default' => 20,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    |
    | تنظیمات مربوط به آپلود فایل
    |
    */
    
    'upload' => [
        'max_size' => 10240, // KB
        'allowed_images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'allowed_videos' => ['mp4', 'avi', 'mov', 'wmv'],
        'allowed_audios' => ['mp3', 'wav', 'ogg'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | SFTP Settings
    |--------------------------------------------------------------------------
    |
    | تنظیمات SFTP برای ذخیره‌سازی فایل‌ها
    |
    */
    
    'sftp' => [
        'url' => env('SFTP_URL', 'https://louhnyrh.lexoyacloud.ir'),
        'host' => env('SFTP_HOST', ''),
        'port' => env('SFTP_PORT', 22),
        'username' => env('SFTP_USERNAME', ''),
        'password' => env('SFTP_PASSWORD', ''),
        'root' => env('SFTP_ROOT', '/'),
    ],
    
];

