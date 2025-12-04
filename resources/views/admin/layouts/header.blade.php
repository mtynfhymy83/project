<div id="header" class="bg-dark border-light">
    <a href="{{ url('/') }}" target="_blank">
        @php
            $logo = $site_logo ?? '';
            $relLogo = $logo ? str_replace(public_path(), '', $logo) : '';
            $relLogo = ltrim(str_replace('\\', '/', $relLogo), '/');
        @endphp
        
        @if($relLogo)
        <img src="{{ asset($relLogo) }}" style="max-height:50px;float:right" alt="Logo">
        @endif
        
        <h3 style="float:right;margin:12px 10px">{{ $title ?? 'پنل مدیریت' }}</h3>
    </a>
    
    <div class="admin-user-logged-in">
        <div class="user-avatar border-light">
            <img src="{{ auth()->user()->avatar_url ?? asset('images/default-avatar.png') }}" alt="Avatar"/>
        </div>

        <span class="user-name bg-light" dir="ltr">
            <i class="fa fa-user"></i> &nbsp; 
            <span dir="auto">{{ auth()->user()->displayname ?? auth()->user()->name }}</span>
        </span>

        <a href="{{ route('admin.logout') }}" title="خروج" class="user-logout bg-light">
            <i class="fa fa-power-off"></i>
        </a>
    </div>

    <div class="clear"></div>
</div>

<div id="container">

