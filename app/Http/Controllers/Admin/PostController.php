<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * نمایش لیست پست‌ها
     */
    public function index(Request $request)
    {
        $query = Post::with(['category', 'author']);
        
        // جستجو
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }
        
        // فیلتر براساس دسته‌بندی
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        
        // فیلتر براساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // مرتب‌سازی
        $query->orderBy('created_at', 'desc');
        
        // Pagination
        $posts = $query->paginate(30);
        
        // دریافت لیست دسته‌بندی‌ها برای فیلتر
        $categories = Category::orderBy('name')->get();
        
        return view('admin.posts.index', [
            'title' => 'مدیریت پست‌ها',
            'posts' => $posts,
            'categories' => $categories
        ]);
    }
    
    /**
     * نمایش فرم ایجاد پست جدید
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        
        return view('admin.posts.create', [
            'title' => 'پست جدید',
            'categories' => $categories
        ]);
    }
    
    /**
     * ذخیره پست جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'thumbnail' => 'nullable|image|max:2048',
            'status' => 'required|in:published,draft,pending',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // ایجاد slug
        $slug = Str::slug($request->title);
        $slugCount = Post::where('slug', 'LIKE', $slug . '%')->count();
        if ($slugCount > 0) {
            $slug = $slug . '-' . ($slugCount + 1);
        }
        
        // آپلود تصویر
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('posts', 'public');
        }
        
        $post = Post::create([
            'title' => $request->title,
            'slug' => $slug,
            'content' => $request->content,
            'excerpt' => $request->excerpt,
            'category_id' => $request->category_id,
            'thumbnail' => $thumbnailPath,
            'status' => $request->status,
            'author_id' => auth()->id(),
            'published_at' => $request->status === 'published' ? now() : null,
        ]);
        
        return redirect()->route('admin.posts.index')
            ->with('success', 'پست با موفقیت ایجاد شد');
    }
    
    /**
     * نمایش فرم ویرایش پست
     */
    public function edit($id)
    {
        $post = Post::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        
        return view('admin.posts.edit', [
            'title' => 'ویرایش پست',
            'post' => $post,
            'categories' => $categories
        ]);
    }
    
    /**
     * بروزرسانی پست
     */
    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'thumbnail' => 'nullable|image|max:2048',
            'status' => 'required|in:published,draft,pending',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // بروزرسانی slug اگر عنوان تغییر کرده باشد
        if ($post->title !== $request->title) {
            $slug = Str::slug($request->title);
            $slugCount = Post::where('slug', 'LIKE', $slug . '%')
                ->where('id', '!=', $id)
                ->count();
            if ($slugCount > 0) {
                $slug = $slug . '-' . ($slugCount + 1);
            }
        } else {
            $slug = $post->slug;
        }
        
        // آپلود تصویر جدید
        $thumbnailPath = $post->thumbnail;
        if ($request->hasFile('thumbnail')) {
            // حذف تصویر قدیمی
            if ($post->thumbnail && \Storage::disk('public')->exists($post->thumbnail)) {
                \Storage::disk('public')->delete($post->thumbnail);
            }
            $thumbnailPath = $request->file('thumbnail')->store('posts', 'public');
        }
        
        $post->update([
            'title' => $request->title,
            'slug' => $slug,
            'content' => $request->content,
            'excerpt' => $request->excerpt,
            'category_id' => $request->category_id,
            'thumbnail' => $thumbnailPath,
            'status' => $request->status,
            'published_at' => $request->status === 'published' && !$post->published_at ? now() : $post->published_at,
        ]);
        
        return redirect()->route('admin.posts.index')
            ->with('success', 'پست با موفقیت بروزرسانی شد');
    }
    
    /**
     * حذف پست
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        
        // حذف تصویر
        if ($post->thumbnail && \Storage::disk('public')->exists($post->thumbnail)) {
            \Storage::disk('public')->delete($post->thumbnail);
        }
        
        $post->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'پست با موفقیت حذف شد'
        ]);
    }
    
    /**
     * مدیریت دسته‌بندی‌ها
     */
    public function category()
    {
        $categories = Category::withCount('posts')->orderBy('name')->get();
        
        return view('admin.posts.category', [
            'title' => 'دسته‌بندی پست‌ها',
            'categories' => $categories
        ]);
    }
}

