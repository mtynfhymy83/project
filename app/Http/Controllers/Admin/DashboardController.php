<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Models\Payment;
use App\Models\Comment;

class DashboardController extends Controller
{
    /**
     * نمایش داشبورد اصلی پنل ادمین
     */
    public function index()
    {
        // آمار اصلی
        $postsCount = Post::count();
        $usersCount = User::count();
        $paymentsCount = Payment::count();
        $commentsCount = Comment::count();
        
        // داده‌های اضافی برای نمایش در داشبورد
        $home_data = [
            [
                'name' => 'نظرات',
                'icon' => 'commenting-o',
                'count' => $commentsCount,
                'link' => route('admin.comments.index')
            ],
            [
                'name' => 'پرداخت‌ها',
                'icon' => 'credit-card',
                'count' => $paymentsCount,
                'link' => route('admin.payment.index')
            ],
        ];
        
        return view('admin.dashboard.index', [
            'title' => 'داشبورد',
            'postsCount' => $postsCount,
            'usersCount' => $usersCount,
            'home_data' => $home_data,
        ]);
    }
    
    /**
     * نمایش آمار و گزارش‌ها
     */
    public function statistics()
    {
        // پیاده‌سازی آمار و نمودارها
        return view('admin.dashboard.statistics', [
            'title' => 'آمار و گزارش‌ها'
        ]);
    }
}

