@extends('admin.layouts.app')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <h3 class="pull-right">مدیریت پست‌ها</h3>
        @can('creat_post')
        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary pull-left">
            <i class="fa fa-plus"></i> پست جدید
        </a>
        @endcan
    </div>
    
    <div class="panel-body">
        {{-- فرم جستجو و فیلتر --}}
        <div class="search-box mb-3">
            <form method="GET" action="{{ route('admin.posts.index') }}" class="form-inline">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="جستجو در عنوان..." 
                           value="{{ request('search') }}">
                </div>
                
                <div class="form-group">
                    <select name="category" class="form-control">
                        <option value="">همه دسته‌ها</option>
                        @isset($categories)
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                
                <div class="form-group">
                    <select name="status" class="form-control">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>منتشر شده</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-info">
                    <i class="fa fa-search"></i> جستجو
                </button>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-default">
                    <i class="fa fa-refresh"></i> پاک کردن
                </a>
            </form>
        </div>
        
        {{-- جدول پست‌ها --}}
        <div class="table-responsive">
            <table id="table" class="table light2">
                <thead>
                    <tr>
                        <th width="50"></th>
                        <th>عنوان</th>
                        <th>دسته‌بندی</th>
                        <th>نویسنده</th>
                        <th class="text-center">وضعیت</th>
                        <th>تاریخ انتشار</th>
                        <th class="text-center">بازدید</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                    <tr>
                        <td class="text-center">
                            @if($post->thumbnail)
                            <img src="{{ $post->thumbnail_url }}" 
                                 width="50" height="50" 
                                 style="object-fit: cover; border-radius: 4px;">
                            @else
                            <i class="fa fa-file-text fa-2x text-muted"></i>
                            @endif
                        </td>
                        <td>
                            <div class="wb">
                                <a href="{{ route('admin.posts.edit', $post->id) }}">
                                    {{ $post->title }}
                                </a>
                            </div>
                        </td>
                        <td>
                            @if($post->category)
                            <span class="label label-default">{{ $post->category->name }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $post->author->displayname ?? $post->author->name }}</td>
                        <td class="text-center">
                            @switch($post->status)
                                @case('published')
                                    <span class="label label-success">منتشر شده</span>
                                    @break
                                @case('draft')
                                    <span class="label label-warning">پیش‌نویس</span>
                                    @break
                                @case('pending')
                                    <span class="label label-info">در انتظار</span>
                                    @break
                                @default
                                    <span class="label label-default">{{ $post->status }}</span>
                            @endswitch
                        </td>
                        <td>{{ $post->published_at_jalali ?? $post->published_at }}</td>
                        <td class="text-center">
                            <span class="badge">{{ $post->views_count ?? 0 }}</span>
                        </td>
                        <td class="text-center">
                            @can('edit_post')
                            <a href="{{ route('admin.posts.edit', $post->id) }}" 
                               class="btn btn-sm btn-primary" title="ویرایش">
                                <i class="fa fa-pencil"></i>
                            </a>
                            @endcan
                            
                            <a href="{{ route('post.show', $post->slug) }}" 
                               target="_blank" 
                               class="btn btn-sm btn-info" title="مشاهده">
                                <i class="fa fa-eye"></i>
                            </a>
                            
                            @can('delete_post')
                            <button class="btn btn-sm btn-danger" 
                                    onclick="deletePost({{ $post->id }})" title="حذف">
                                <i class="fa fa-trash"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="fa fa-inbox fa-3x"></i>
                            <p>هیچ پستی یافت نشد</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="text-center">
            {{ $posts->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deletePost(postId) {
    if(!confirm('آیا از حذف این پست مطمئن هستید؟')) return;
    
    $.ajax({
        type: "DELETE",
        url: baseUrl + 'admin/posts/' + postId,
        dataType: "json",
        success: function(data) {
            if(data.success) {
                location.reload();
            } else {
                alert(data.message || 'خطا در حذف پست');
            }
        },
        error: function() {
            alert('خطا در اتصال به سرور');
        }
    });
}
</script>
@endpush

