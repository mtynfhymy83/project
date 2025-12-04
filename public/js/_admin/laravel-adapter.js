/**
 * Laravel Adapter JS
 * این فایل برای سازگاری کدهای JavaScript قدیمی با Laravel طراحی شده است
 */

(function($) {
    'use strict';
    
    /**
     * تنظیم CSRF Token برای همه درخواست‌های AJAX
     */
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    /**
     * Helper function برای نمایش پیام‌های notify
     */
    window.notify = function(message, type) {
        // type: 0 = success, 1 = error, 2 = warning, 'success', 'error', 'warning'
        let alertClass = 'alert-info';
        
        if (type === 0 || type === 'success') {
            alertClass = 'alert-success';
        } else if (type === 1 || type === 'error') {
            alertClass = 'alert-danger';
        } else if (type === 2 || type === 'warning') {
            alertClass = 'alert-warning';
        }
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };
    
    /**
     * Helper function برای نمایش alert box
     */
    window.get_alert = function(data) {
        let type = 'info';
        if (data.status === 0 || data.status === 'success') {
            type = 'success';
        } else if (data.status === 1 || data.status === 'error') {
            type = 'danger';
        } else if (data.status === 2 || data.status === 'warning') {
            type = 'warning';
        }
        
        return `<div class="alert alert-${type}">${data.msg || data.message}</div>`;
    };
    
    /**
     * Helper function برای نمایش popup screen
     */
    window.popupScreen = function(content) {
        // حذف popup قبلی اگر وجود دارد
        $('.full-screen').remove();
        
        if (!content || content === '') {
            return;
        }
        
        const popup = $(`
            <div class="full-screen" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9998; overflow-y: auto; padding: 20px;">
                <div class="popup-content" style="background: white; border-radius: 8px; padding: 30px; max-width: 900px; margin: 0 auto; position: relative;">
                    <button type="button" class="close-popup" style="position: absolute; top: 10px; left: 10px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                    <div class="popup-body"></div>
                </div>
            </div>
        `);
        
        popup.find('.popup-body').html(content);
        $('body').append(popup);
        
        // بستن popup با کلیک روی دکمه close
        popup.find('.close-popup').on('click', function() {
            popup.fadeOut(function() {
                $(this).remove();
            });
        });
        
        // بستن popup با کلیک روی پس‌زمینه
        popup.on('click', function(e) {
            if ($(e.target).hasClass('full-screen')) {
                $(this).fadeOut(function() {
                    $(this).remove();
                });
            }
        });
        
        return popup;
    };
    
    /**
     * بستن popup
     */
    window.ClosePopupScreen = function(element) {
        if (element && element.length) {
            element.fadeOut(function() {
                $(this).remove();
            });
        } else {
            $('.full-screen').fadeOut(function() {
                $(this).remove();
            });
        }
    };
    
    /**
     * Helper function برای ساخت URL تصویر thumbnail
     */
    window.thumb = function(path, size) {
        if (!path) return '';
        
        // اگر path از قبل کامل است
        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }
        
        // اگر path با / شروع نمی‌شود، اضافه کن
        if (!path.startsWith('/')) {
            path = '/' + path;
        }
        
        // در Laravel، تصاویر معمولاً در storage/public قرار دارند
        if (!path.startsWith('/storage/')) {
            path = '/storage' + path;
        }
        
        return baseUrl + path.replace(/^\/+/, '');
    };
    
    /**
     * Helper function برای login check
     */
    window.login = function(callback) {
        window.location.href = baseUrl + 'admin/login';
    };
    
    /**
     * تبدیل URL های CodeIgniter به Laravel
     */
    window.convertUrl = function(url) {
        // حذف index.php از URL
        url = url.replace(/\/index\.php\//g, '/');
        
        return url;
    };
    
    /**
     * Override $.ajax برای تبدیل URL ها
     */
    const originalAjax = $.ajax;
    $.ajax = function(settings) {
        if (typeof settings === 'string') {
            settings = { url: settings };
        }
        
        // تبدیل URL
        if (settings.url) {
            settings.url = convertUrl(settings.url);
            
            // اطمینان از وجود baseUrl در ابتدای URL
            if (!settings.url.startsWith('http') && !settings.url.startsWith('/')) {
                settings.url = baseUrl + settings.url;
            }
        }
        
        return originalAjax.call($, settings);
    };
    
    /**
     * Menu toggle functionality
     */
    $(document).on('click', '.toggle-sub-menu', function(e) {
        e.preventDefault();
        $(this).closest('li').find('> ul').slideToggle();
        $(this).toggleClass('fa-angle-double-down fa-angle-double-up');
    });
    
    /**
     * Auto dismiss alerts
     */
    setTimeout(function() {
        $('.alert').not('.alert-permanent').fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
    
})(jQuery);

// اضافه کردن loading spinner
(function() {
    const spinnerHtml = `
        <style>
            .ajax-loading {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 9999;
                display: none;
            }
            .ajax-loading.show {
                display: block;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
        <div class="ajax-loading">
            <div class="spinner"></div>
        </div>
    `;
    
    $('body').append(spinnerHtml);
    
    // نمایش loading در هنگام AJAX request
    $(document).ajaxStart(function() {
        $('.ajax-loading').addClass('show');
    }).ajaxStop(function() {
        $('.ajax-loading').removeClass('show');
    });
})();

console.log('Laravel Adapter loaded successfully!');

