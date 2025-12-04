<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * بررسی دسترسی کاربر به پنل ادمین
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // بررسی لاگین بودن
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login')
                ->with('error', 'لطفا ابتدا وارد شوید');
        }
        
        // بررسی دسترسی ادمین
        $user = auth()->user();
        
        // روش 1: بررسی با استفاده از فیلد level
        if (!in_array($user->level, ['admin', 'super_admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden.'], 403);
            }
            
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'شما دسترسی به پنل مدیریت ندارید');
        }
        
        // روش 2: بررسی با استفاده از permission (اگر از package استفاده می‌کنید)
        // if (!$user->hasRole('admin')) {
        //     return redirect()->route('home')->with('error', 'دسترسی غیرمجاز');
        // }
        
        return $next($request);
    }
}

