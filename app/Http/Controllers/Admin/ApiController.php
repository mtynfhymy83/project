<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    /**
     * صفحه اصلی API - برای سازگاری با کد قدیمی
     */
    public function index()
    {
        return response()->json(['status' => 'ok']);
    }
    
    /**
     * دریافت اطلاعات کاربر برای ویرایش
     */
    public function getUserInfo($id)
    {
        try {
            $user = User::findOrFail($id);
            
            return response()->json([
                'done' => true,
                'user' => $user->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'done' => false,
                'msg' => 'کاربر یافت نشد'
            ]);
        }
    }
    
    /**
     * بروزرسانی اطلاعات کاربر
     */
    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $data = $request->except(['password', '_token']);
            
            // اگر پسورد جدید وارد شده باشد
            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
            }
            
            $user->update($data);
            
            return response()->json([
                'status' => 0,
                'msg' => 'اطلاعات کاربر با موفقیت بروزرسانی شد',
                'done' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 1,
                'msg' => 'خطا در بروزرسانی اطلاعات',
                'done' => false
            ]);
        }
    }
    
    /**
     * دریافت لیست کتاب‌های کاربر
     */
    public function getUserBooks($userId)
    {
        try {
            $user = User::with('books')->findOrFail($userId);
            
            return response()->json([
                'done' => true,
                'user' => $user->toArray(),
                'books' => $user->books->map(function($book) {
                    return [
                        'ubid' => $book->pivot->id ?? $book->id,
                        'title' => $book->title,
                        'cname' => $book->category->name ?? '-'
                    ];
                })->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'done' => false,
                'msg' => 'خطا در دریافت اطلاعات'
            ]);
        }
    }
    
    /**
     * افزودن کتاب به کاربر
     */
    public function addUserBooks(Request $request)
    {
        try {
            $userId = $request->uid;
            $bookId = $request->bid;
            
            $user = User::findOrFail($userId);
            
            // بررسی اینکه قبلاً این کتاب به کاربر اضافه نشده باشد
            if (!$user->books()->where('book_id', $bookId)->exists()) {
                $user->books()->attach($bookId);
            }
            
            // بازگرداندن لیست جدید
            $books = $user->books()->with('category')->get()->map(function($book) {
                return [
                    'ubid' => $book->pivot->id ?? $book->id,
                    'title' => $book->title,
                    'cname' => $book->category->name ?? '-'
                ];
            })->toArray();
            
            return response()->json([
                'status' => 0,
                'books' => $books
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 1,
                'msg' => 'خطا در افزودن کتاب'
            ]);
        }
    }
    
    /**
     * حذف کتاب از لیست کاربر
     */
    public function deleteUserBooks($userBookId)
    {
        try {
            // پیاده‌سازی حذف کتاب از کاربر
            // باید relation مناسب را پیاده‌سازی کنید
            
            return response()->json(['done' => true]);
        } catch (\Exception $e) {
            return response()->json(['done' => false]);
        }
    }
    
    /**
     * جستجوی کتاب‌ها برای autocomplete
     */
    public function getBooks($search)
    {
        try {
            $books = Post::where('type', 'book')
                ->where('title', 'LIKE', "%{$search}%")
                ->limit(10)
                ->get(['id', 'title'])
                ->map(function($book) {
                    return [
                        'idx' => $book->id,
                        'value' => $book->title,
                        'label' => $book->title,
                        'title' => $book->title
                    ];
                })->toArray();
            
            return response()->json(['result' => $books]);
        } catch (\Exception $e) {
            return response()->json(['result' => []]);
        }
    }
    
    /**
     * آپلود فایل
     */
    public function upload(Request $request)
    {
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('uploads', 'public');
                
                return response()->json([
                    'done' => true,
                    'url' => Storage::url($path),
                    'path' => $path
                ]);
            }
            
            return response()->json([
                'done' => false,
                'msg' => 'فایلی آپلود نشده است'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'done' => false,
                'msg' => 'خطا در آپلود فایل'
            ]);
        }
    }
    
    /**
     * حذف عمومی - برای استفاده در جداول
     */
    public function delete($table, $id)
    {
        try {
            // این یک روش ساده است - بهتر است برای هر مدل controller جداگانه داشته باشید
            switch ($table) {
                case 'users':
                    $model = User::findOrFail($id);
                    break;
                case 'posts':
                    $model = Post::findOrFail($id);
                    break;
                default:
                    throw new \Exception('Invalid table');
            }
            
            $model->delete();
            
            return response()->json([
                'done' => true,
                'msg' => 'حذف با موفقیت انجام شد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'done' => false,
                'msg' => 'خطا در حذف'
            ]);
        }
    }
    
    /**
     * دریافت اطلاعات اشتراک‌های کاربر
     */
    public function getDataMembership($userId)
    {
        try {
            $user = User::with('memberships')->findOrFail($userId);
            
            $result = $user->memberships->map(function($membership) {
                return [
                    $membership->type ?? 'عضویت',
                    $membership->title ?? '-',
                    $membership->months ?? '-',
                    $membership->start_date ?? '-',
                    $membership->end_date ?? '-',
                    $membership->description ?? '-',
                    $membership->id,
                    $membership->pivot->id ?? $membership->id
                ];
            })->toArray();
            
            return response()->json([
                'done' => true,
                'user' => $user->toArray(),
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'done' => false,
                'msg' => 'خطا در دریافت اطلاعات'
            ]);
        }
    }
}

