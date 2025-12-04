@extends('admin.layouts.app')

@section('content')
<div class="dashboard">
    <div class="row">
        @php
            $colors = ['#E08807', '#CB3712', '#9C2D60', '#486340', '#555', '#685177', '#B18B6B'];
            $i = -1;
        @endphp
        
        {{-- نمونه برای نمایش آمار پست‌ها --}}
        @if(auth()->user()->can('read_post'))
            @php $i++; if($i >= count($colors)) $i = 0; @endphp
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="item" style="background-color:{{ $colors[$i] }}">
                    <a href="{{ route('admin.posts.index') }}">
                        <i class="fa fa-file-text item-icon"></i>
                        <h3 class="item-name">پست‌ها</h3>
                        <i class="fa fa-angle-double-left"></i>
                        <i class="item-count">{{ $postsCount ?? 0 }}</i>
                    </a>
                    <div class="clearfix"></div>
                    <hr/>
                    @can('create_post')
                        <a href="{{ route('admin.posts.create') }}">
                            <i class="fa fa-plus-circle fa-lg"></i>
                        </a>
                    @endcan
                    @can('category_post')
                        <a href="{{ route('admin.posts.category') }}">
                            <i class="fa fa-bookmark fa-lg"></i>
                        </a>
                    @endcan
                </div>
            </div>
        @endif
        
        {{-- نمونه برای نمایش آمار کاربران --}}
        @if(auth()->user()->can('manage_users'))
            @php $i++; if($i >= count($colors)) $i = 0; @endphp
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="item" style="background-color:{{ $colors[$i] }}">
                    <a href="{{ route('admin.users.index') }}">
                        <i class="fa fa-user item-icon"></i>
                        <h3 class="item-name">کاربران</h3>
                        <i class="fa fa-angle-double-left"></i>
                        <i class="item-count">{{ $usersCount ?? 0 }}</i>
                    </a>
                    <div class="clearfix"></div>
                </div>
            </div>
        @endif
        
        {{-- نمایش سایر آمارها --}}
        @isset($home_data)
            @foreach($home_data as $data)
                @php $i++; if($i >= count($colors)) $i = 0; @endphp
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="item" style="background-color:{{ $colors[$i] }}">
                        <a href="{{ $data['link'] }}">
                            <i class="fa fa-{{ $data['icon'] }} item-icon"></i>
                            <h3 class="item-name">{{ $data['name'] }}</h3>
                            <i class="fa fa-angle-double-left"></i>
                            <i class="item-count">{{ $data['count'] }}</i>
                        </a>
                        <div class="clearfix"></div>
                    </div>
                </div>
            @endforeach
        @endisset
    </div>
</div>

@can('site_visits')
    {{-- اگر نیاز به نمایش نمودار دارید اینجا قرار دهید --}}
@endcan
@endsection

