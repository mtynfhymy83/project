<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * نمایش لیست کاربران
     */
    public function index(Request $request)
    {
        $query = User::query();
        
        // جستجو
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('displayname', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('tel', 'LIKE', "%{$search}%");
            });
        }
        
        // فیلتر براساس سطح دسترسی
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        
        // فیلتر براساس وضعیت
        if ($request->filled('active')) {
            $query->where('active', $request->active);
        }
        
        // مرتب‌سازی
        $query->orderBy('created_at', 'desc');
        
        // Pagination
        $users = $query->paginate(60);
        
        // اضافه کردن تعداد کتاب‌ها و اشتراک‌ها
        $users->getCollection()->transform(function ($user) {
            $user->books_count = $user->books()->count();
            $user->memberships_count = $user->memberships()->count();
            return $user;
        });
        
        return view('admin.users.index', [
            'title' => 'مدیریت کاربران',
            'users' => $users
        ]);
    }
    
    /**
     * نمایش فرم ایجاد کاربر جدید
     */
    public function create()
    {
        $levels = $this->getUserLevels();
        
        return view('admin.users.create', [
            'title' => 'کاربر جدید',
            'levels' => $levels
        ]);
    }
    
    /**
     * ذخیره کاربر جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:6',
            'displayname' => 'required|max:255',
            'tel' => 'nullable|max:20',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'displayname' => $request->displayname,
            'tel' => $request->tel,
            'level' => $request->level ?? 'user',
            'active' => $request->active ?? 1,
        ]);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد');
    }
    
    /**
     * نمایش فرم ویرایش کاربر
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $levels = $this->getUserLevels();
        
        return view('admin.users.edit', [
            'title' => 'ویرایش کاربر',
            'user' => $user,
            'levels' => $levels
        ]);
    }
    
    /**
     * بروزرسانی کاربر
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:255|unique:users,username,' . $id,
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'displayname' => 'required|max:255',
            'tel' => 'nullable|max:20',
            'password' => 'nullable|min:6',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $data = [
            'username' => $request->username,
            'email' => $request->email,
            'displayname' => $request->displayname,
            'tel' => $request->tel,
            'level' => $request->level ?? $user->level,
            'active' => $request->active ?? $user->active,
            'support' => $request->support ?? 0,
        ];
        
        // اگر پسورد جدید وارد شده باشد
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        
        $user->update($data);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت بروزرسانی شد');
    }
    
    /**
     * حذف کاربر
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // جلوگیری از حذف خودمان
        if ($user->id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما نمی‌توانید حساب کاربری خود را حذف کنید'
            ]);
        }
        
        $user->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت حذف شد'
        ]);
    }
    
    /**
     * نمایش صفحه سطوح دسترسی
     */
    public function levels()
    {
        // پیاده‌سازی مدیریت سطوح دسترسی
        return view('admin.users.levels', [
            'title' => 'سطوح دسترسی'
        ]);
    }
    
    /**
     * نمایش نمودار آماری کاربران
     */
    public function chart()
    {
        // پیاده‌سازی نمودار آماری
        return view('admin.users.chart', [
            'title' => 'آمار کاربران'
        ]);
    }
    
    /**
     * دریافت سطوح دسترسی کاربران
     */
    private function getUserLevels()
    {
        // این را باید از دیتابیس بخوانید
        return [
            'user' => 'کاربر',
            'admin' => 'ادمین',
            'teacher' => 'استاد',
        ];
    }
}

