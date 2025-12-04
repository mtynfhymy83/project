<?php

if (!function_exists('jalali_date')) {
    /**
     * تبدیل تاریخ میلادی به شمسی
     * 
     * @param string|null $date
     * @param string $format
     * @return string|null
     */
    function jalali_date($date = null, $format = 'Y/m/d H:i')
    {
        if (!$date) {
            return null;
        }
        
        // شما می‌توانید از پکیج morilog/jalali یا hekmatinasser/verta استفاده کنید
        // composer require hekmatinasser/verta
        
        try {
            if (class_exists('\Hekmatinasser\Verta\Verta')) {
                return \Hekmatinasser\Verta\Verta::instance($date)->format($format);
            }
            
            // اگر پکیج نصب نیست، تاریخ میلادی را برگردان
            return date($format, strtotime($date));
        } catch (\Exception $e) {
            return date($format, strtotime($date));
        }
    }
}

if (!function_exists('get_user_avatar')) {
    /**
     * دریافت آدرس آواتار کاربر
     * 
     * @param \App\Models\User|null $user
     * @param int $size
     * @return string
     */
    function get_user_avatar($user = null, $size = 150)
    {
        if (!$user) {
            $user = auth()->user();
        }
        
        if (!$user) {
            return asset('images/default-avatar.png');
        }
        
        if ($user->avatar) {
            // اگر آواتار کامل URL است
            if (filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                return $user->avatar;
            }
            
            // اگر مسیر محلی است
            return asset('storage/' . $user->avatar);
        }
        
        return asset('images/default-avatar.png');
    }
}

if (!function_exists('get_thumbnail')) {
    /**
     * دریافت آدرس thumbnail
     * 
     * @param string|null $path
     * @param int $size
     * @return string
     */
    function get_thumbnail($path, $size = 300)
    {
        if (!$path) {
            return asset('images/no-image.png');
        }
        
        // اگر URL کامل است
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // حذف storage/ از ابتدای path اگر وجود دارد
        $path = ltrim($path, '/');
        $path = str_replace('storage/', '', $path);
        
        return asset('storage/' . $path);
    }
}

if (!function_exists('can_user')) {
    /**
     * بررسی دسترسی کاربر
     * 
     * @param string $permission
     * @param \App\Models\User|null $user
     * @return bool
     */
    function can_user($permission, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        
        if (!$user) {
            return false;
        }
        
        // اگر از package permission استفاده می‌کنید
        // return $user->can($permission);
        
        // روش ساده: بررسی level
        if (in_array($user->level, ['admin', 'super_admin'])) {
            return true;
        }
        
        // بررسی permission های خاص
        // این را باید بر اساس ساختار دیتابیس خودتان پیاده‌سازی کنید
        return false;
    }
}

if (!function_exists('get_site_logo')) {
    /**
     * دریافت لوگوی سایت
     * 
     * @return string
     */
    function get_site_logo()
    {
        // دریافت از تنظیمات
        $logo = config('settings.site_logo');
        
        if (!$logo) {
            return asset('images/logo.png');
        }
        
        return asset('storage/' . $logo);
    }
}

if (!function_exists('get_setting')) {
    /**
     * دریافت تنظیمات سایت
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get_setting($key, $default = null)
    {
        // شما می‌توانید این را از دیتابیس یا cache بخوانید
        return config('settings.' . $key, $default);
    }
}

if (!function_exists('format_number')) {
    /**
     * فرمت کردن اعداد به فارسی
     * 
     * @param int|float $number
     * @return string
     */
    function format_number($number)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        $formatted = number_format($number);
        
        return str_replace($english, $persian, $formatted);
    }
}

if (!function_exists('sanitize_output')) {
    /**
     * پاکسازی خروجی برای نمایش
     * 
     * @param string $text
     * @return string
     */
    function sanitize_output($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_post_types')) {
    /**
     * دریافت انواع پست‌ها
     * 
     * @return array
     */
    function get_post_types()
    {
        return [
            'post' => [
                'name' => 'پست',
                'g_name' => 'پست‌ها',
                'icon' => 'file-text',
                'support' => ['category', 'comments']
            ],
            'book' => [
                'name' => 'کتاب',
                'g_name' => 'کتاب‌ها',
                'icon' => 'book',
                'support' => ['category', 'author', 'publisher']
            ],
            'video' => [
                'name' => 'ویدیو',
                'g_name' => 'ویدیوها',
                'icon' => 'video-camera',
                'support' => ['category']
            ],
        ];
    }
}

if (!function_exists('truncate_text')) {
    /**
     * کوتاه کردن متن
     * 
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    function truncate_text($text, $length = 100, $suffix = '...')
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }
}

