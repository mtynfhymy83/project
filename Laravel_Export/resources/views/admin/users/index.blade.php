@extends('admin.layouts.app')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <h3 class="pull-right">مدیریت کاربران</h3>
        @can('create_user')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary pull-left">
            <i class="fa fa-plus"></i> کاربر جدید
        </a>
        @endcan
    </div>
    
    <div class="panel-body">
        {{-- فرم جستجو --}}
        <div class="search-box mb-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="form-inline">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="جستجو (نام، ایمیل، تلفن...)" 
                           value="{{ request('search') }}">
                </div>
                <button type="submit" class="btn btn-info">
                    <i class="fa fa-search"></i> جستجو
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-default">
                    <i class="fa fa-refresh"></i> پاک کردن
                </a>
            </form>
        </div>
        
        {{-- جدول کاربران --}}
        <div class="table-responsive">
            <table id="table" class="table light2">
                <thead>
                    <tr>
                        <th width="50"></th>
                        <th>نام نمایشی</th>
                        <th>نام کاربری</th>
                        <th>تلفن همراه</th>
                        <th>نقش</th>
                        <th>ایمیل</th>
                        <th class="text-center">وضعیت</th>
                        <th>تاریخ ثبت نام</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="text-center">
                            <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}" 
                                 width="40" height="40" 
                                 style="cursor:pointer; border-radius: 50%;"
                                 onclick="editUser({{ $user->id }})">
                        </td>
                        <td>
                            <div class="wb" style="cursor:pointer" onclick="editUser({{ $user->id }})">
                                {{ $user->displayname }}
                            </div>
                        </td>
                        <td>
                            <div class="wb">{{ $user->username }}</div>
                        </td>
                        <td>{{ $user->tel }}</td>
                        <td>
                            <span class="label label-info">{{ $user->level_name ?? 'کاربر' }}</span>
                        </td>
                        <td class="ar">{{ $user->email }}</td>
                        <td class="text-center">
                            @if($user->active == 1)
                                <i class="fa fa-check-circle-o fa-lg text-success" title="فعال"></i>
                            @else
                                <i class="fa fa-ban fa-lg text-danger" title="مسدود"></i>
                            @endif
                        </td>
                        <td>{{ $user->created_at_jalali ?? $user->created_at }}</td>
                        <td class="text-center">
                            @can('edit_user')
                            <button class="btn btn-sm btn-primary" onclick="editUser({{ $user->id }})">
                                <i class="fa fa-pencil"></i>
                            </button>
                            @endcan
                            
                            @can('delete_user')
                            <button class="btn btn-sm btn-danger" 
                                    onclick="deleteRow(this, 'users', {{ $user->id }})">
                                <i class="fa fa-trash"></i>
                            </button>
                            @endcan
                            
                            {{-- دکمه‌های اضافی --}}
                            <button class="btn btn-sm btn-info" 
                                    onclick="showUserBooks({{ $user->id }})" 
                                    title="کتاب‌های کاربر">
                                <i class="fa fa-book"></i>
                                <span class="badge">{{ $user->books_count ?? 0 }}</span>
                            </button>
                            
                            <button class="btn btn-sm btn-warning" 
                                    onclick="showMembership(null, {{ $user->id }}, 1)" 
                                    title="اشتراک‌ها">
                                <i class="fa fa-share-alt"></i>
                                <span class="badge">{{ $user->memberships_count ?? 0 }}</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <i class="fa fa-inbox fa-3x"></i>
                            <p>هیچ کاربری یافت نشد</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="text-center">
            {{ $users->links() }}
        </div>
    </div>
</div>

{{-- Modal برای ویرایش کاربر --}}
<div class="hidden">
    <div class="view-sample">
        {{-- محتوای modal از فایل JS لود می‌شود --}}
    </div>
</div>
@endsection

@push('scripts')
<script>
function editUser(userId) {
    var $html = $('<div/>', {'id': 'edit-user'});
    $html.append('<div class="text-center"><i class="l c-c blue h3"></i></div>');
    popupScreen($html);
    
    $.ajax({
        type: "POST",
        url: baseUrl + 'admin/api/getUserInfo/' + userId,
        dataType: "json",
        success: function(data) {
            if(data == "login") {
                popupScreen('');
                return;
            }
            
            if(!data.done) {
                $html.html('<h3 class="text-warning text-center">' + data.msg + '</h3>');
                return;
            }
            
            // اینجا فرم ویرایش را نمایش دهید
            // می‌توانید از یک Blade component یا partial استفاده کنید
        },
        error: function() {
            $html.html('<h3 class="text-warning text-center">خطا در اتصال</h3>');
        }
    });
}

function deleteRow(btn, table, id) {
    if(!confirm('آیا مطمئن هستید؟')) return;
    
    $.ajax({
        type: "POST",
        url: baseUrl + 'admin/api/delete/' + table + '/' + id,
        dataType: "json",
        success: function(data) {
            if(data.done) {
                $(btn).closest('tr').fadeOut();
                notify('حذف با موفقیت انجام شد', 'success');
            } else {
                notify(data.msg || 'خطا در حذف', 'error');
            }
        },
        error: function() {
            notify('خطا در اتصال', 'error');
        }
    });
}

function showUserBooks(userId) {
    // پیاده‌سازی مشابه edit_user
}

function showMembership(btn, userId, isNew) {
    // پیاده‌سازی نمایش اشتراک‌ها
}
</script>
@endpush

