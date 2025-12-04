<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <base href="{{ url('/') }}/">
    
    <title>{{ $title ?? 'پنل مدیریت' }} @isset($_title) - {{ $_title }} @endisset</title>
    
    <meta name="robots" content="noindex,nofollow">
    <meta name="googlebot" content="noindex,nofollow">
    
    @if(isset($favicon))
    <link rel="icon" href="{{ asset($favicon) }}">
    @endif
    
    {{-- CSS Files --}}
    <link rel="stylesheet" href="{{ asset('style/_master/font.css') }}">
    <link rel="stylesheet" href="{{ asset('style/_master/css/_admin/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('style/_master/css/_admin/bootstrap-rtl.css') }}">
    <link rel="stylesheet" href="{{ asset('style/_master/css/_admin/public.css') }}">
    <link rel="stylesheet" href="{{ asset('style/_master/css/_admin/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('style/_master/css/_admin/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('style/_master/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('style/scroll/jquery.mCustomScrollbar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('style/ui.1.12.1/jquery-ui.css') }}">
    
    {{-- Additional CSS --}}
    @stack('styles')
    
    {{-- JS Files --}}
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery-ui.js') }}"></script>
    <script src="{{ asset('js/_admin/public.js') }}"></script>
    <script src="{{ asset('js/_admin/admin.js') }}"></script>
    <script src="{{ asset('js/_admin/media.js') }}"></script>
    <script src="{{ asset('js/mCustomScrollbar.min.js') }}"></script>
    <script src="{{ asset('js/jquery.sticky-kit.min.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.ui.widget.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.iframe-transport.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.fileupload.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.fileupload-process.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/load-image.all.min.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/canvas-to-blob.min.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.fileupload-image.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.fileupload-audio.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.fileupload-video.js') }}"></script>
    <script src="{{ asset('js/jQuery-Uploader/jquery.fileupload-validate.js') }}"></script>
    <script src="{{ asset('js/jquery.ui.datepicker-cc.all.min.js') }}"></script>
    
    {{-- Global JS Variables --}}
    <script>
        var AURL = '{{ route('admin.api.index') }}';
        var URL = '{{ route('admin.api.index') }}';
        var baseUrl = '{{ url('/') }}/';
        var BURL = '{{ url('/') }}/';
        var sftpUrl = '{{ config('app.sftp_url', 'https://louhnyrh.lexoyacloud.ir') }}';
        const emaUserName = "{{ auth()->user()->username ?? '' }}";
        
        // Setup AJAX CSRF Token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    {{-- Additional Scripts --}}
    @stack('scripts-head')
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        {{-- Header --}}
        @include('admin.layouts.header')
        
        {{-- Sidebar and Main Content --}}
        @include('admin.layouts.sidebar')
        
        <div id="main">
            {{-- Flash Messages --}}
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            
            {{-- Main Content --}}
            @yield('content')
            
        </div><!-- end #main -->
        
        <div class="clear"></div>
    </div><!-- end #container -->
    
    <div class="clear"></div>
    
    {{-- Footer --}}
    @include('admin.layouts.footer')
    
</div>
</div>

{{-- Additional Scripts at Bottom --}}
@stack('scripts')

</body>
</html>

