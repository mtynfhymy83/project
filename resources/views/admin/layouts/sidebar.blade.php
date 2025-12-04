<div id="sidebar" class="dark-box">
    <ul>
        @php
            // تعریف منوها
            $menus = [
                'home' => ['permission' => '', 'title' => 'صفحه اصلی', 'icon' => 'home', 'route' => 'admin.dashboard'],
                
                'users' => [
                    'permission' => 'manage_users',
                    'title' => 'کاربران',
                    'icon' => 'user',
                    'route' => 'admin.users.index',
                    'submenu' => [
                        'users' => ['permission' => 'manage_users', 'title' => 'مشاهده', 'icon' => 'user', 'route' => 'admin.users.index'],
                        'levels' => ['permission' => 'edit_user_levels', 'title' => 'سطوح دسترسی', 'icon' => 'tasks', 'route' => 'admin.users.levels'],
                        'adduser' => ['permission' => '', 'title' => 'کاربر جدید', 'icon' => 'pencil', 'route' => 'admin.users.create'],
                        'chart' => ['permission' => '', 'title' => 'آمار', 'icon' => 'bar-chart', 'route' => 'admin.users.chart'],
                        'leitner' => ['permission' => 'leitner', 'title' => 'جعبه لایتنر', 'icon' => 'calendar', 'route' => 'admin.leitner.index'],
                    ]
                ],
                
                'posts' => [
                    'permission' => 'read_post',
                    'title' => 'پست‌ها',
                    'icon' => 'file-text',
                    'route' => 'admin.posts.index',
                    'submenu' => [
                        'primary' => ['permission' => 'read_post', 'title' => 'مشاهده', 'icon' => 'eye', 'route' => 'admin.posts.index'],
                        'add' => ['permission' => 'creat_post', 'title' => 'افزودن', 'icon' => 'pencil', 'route' => 'admin.posts.create'],
                        'category' => ['permission' => 'category_post', 'title' => 'دسته بندی', 'icon' => 'bookmark', 'route' => 'admin.posts.category'],
                    ]
                ],
                
                'azmoon' => ['permission' => 'manage_azmoon', 'title' => 'ثبت آزمون', 'icon' => 'key', 'route' => 'admin.azmoon.index'],
                
                'comments' => ['permission' => 'read_comment', 'title' => 'نظرات', 'icon' => 'commenting-o', 'route' => 'admin.comments.index'],
                
                'geosection' => [
                    'permission' => 'manage_geosection',
                    'title' => 'بخش جغرافیایی',
                    'icon' => 'tags',
                    'route' => 'admin.geosection.index',
                    'submenu' => [
                        'geotype' => ['permission' => 'manage_geotype', 'title' => 'نوع مناطق', 'icon' => 'tags', 'route' => 'admin.geotype.index'],
                        'geosection' => ['permission' => 'manage_geosection', 'title' => 'مناطق جغرافیایی', 'icon' => 'tags', 'route' => 'admin.geosection.index'],
                    ]
                ],
                
                'advertise' => ['permission' => 'manage_advertise', 'title' => 'تبلیغات', 'icon' => 'bank', 'route' => 'admin.advertise.index'],
                
                'payamak' => ['permission' => 'manage_payamak', 'title' => 'پیامک', 'icon' => 'mobile', 'route' => 'admin.payamak.index'],
                
                'dictionary' => [
                    'permission' => 'manage_dictionary',
                    'title' => 'لغتنامه',
                    'icon' => 'tags',
                    'route' => 'admin.dictionary.index',
                    'submenu' => [
                        'dictionary' => ['permission' => 'manage_dictionary', 'title' => 'لغتنامه', 'icon' => 'tags', 'route' => 'admin.dictionary.index'],
                        'diclang' => ['permission' => 'manage_diclang', 'title' => 'زبانهای ترجمه', 'icon' => 'tags', 'route' => 'admin.diclang.index'],
                    ]
                ],
                
                'supplier' => [
                    'permission' => 'manage_supplier',
                    'title' => 'عرضه کنندگان',
                    'icon' => 'tags',
                    'route' => 'admin.supplier.index',
                    'submenu' => [
                        'supplier' => ['permission' => 'manage_supplier', 'title' => 'عرضه کنندگان', 'icon' => 'tags', 'route' => 'admin.supplier.index'],
                        'suppliertype' => ['permission' => 'manage_suppliertype', 'title' => 'نوع', 'icon' => 'tags', 'route' => 'admin.suppliertype.index'],
                    ]
                ],
                
                'tecat' => ['permission' => 'is_supplier', 'title' => 'دسته بندی عنوانی', 'icon' => 'mobile', 'route' => 'admin.tecat.index'],
                
                'mecat' => ['permission' => 'is_supplier', 'title' => 'دسته بندی موضوعی', 'icon' => 'mobile', 'route' => 'admin.mecat.index'],
                
                'membership' => ['permission' => 'is_supplier', 'title' => 'اشتراک', 'icon' => 'group', 'route' => 'admin.membership.index'],
                
                'classonline' => [
                    'permission' => 'is_supplier',
                    'title' => 'کلاسهای آنلاین',
                    'icon' => 'globe',
                    'route' => 'admin.classonline.index',
                    'submenu' => [
                        'classonline' => ['permission' => 'is_supplier', 'title' => 'کلاسهای آنلاین', 'icon' => 'globe', 'route' => 'admin.classonline.index'],
                        'xlsxclassonline' => ['permission' => 'is_supplier', 'title' => 'ورود اکسل', 'icon' => 'file-excel-o', 'route' => 'admin.xlsxclassonline.index'],
                    ]
                ],
                
                'classroom' => ['permission' => 'is_supplier', 'title' => 'کلاسها', 'icon' => 'laptop', 'route' => 'admin.classroom.index'],
                
                'doreh' => [
                    'permission' => 'is_supplier',
                    'title' => 'دوره ها',
                    'icon' => 'tags',
                    'route' => 'admin.doreh.index',
                    'submenu' => [
                        'nezam' => ['permission' => 'is_supplier', 'title' => 'نظام', 'icon' => 'tags', 'route' => 'admin.nezam.index'],
                        'doreh' => ['permission' => 'is_supplier', 'title' => 'دوره ها', 'icon' => 'tags', 'route' => 'admin.doreh.index'],
                        'dorehclass' => ['permission' => 'is_supplier', 'title' => 'کلاسهای دوره', 'icon' => 'tags', 'route' => 'admin.dorehclass.index'],
                        'jalasat' => ['permission' => 'is_supplier', 'title' => 'جلسات کلاسهای دوره', 'icon' => 'tags', 'route' => 'admin.jalasat.index'],
                    ]
                ],
                
                'discount' => ['permission' => 'manage_discount', 'title' => 'کد تخفیف', 'icon' => 'key', 'route' => 'admin.discount.index'],
                
                'payment' => ['permission' => 'manage_payment', 'title' => 'پرداخت ها', 'icon' => 'credit-card', 'route' => 'admin.payment.index'],
                
                'gozaresh' => ['permission' => 'manage_gozaresh', 'title' => 'گزارش مالی', 'icon' => 'diamond', 'route' => 'admin.gozaresh.index'],
                
                'salereport' => ['permission' => 'manage_salereport', 'title' => 'گزارش فروش', 'icon' => 'diamond', 'route' => 'admin.salereport.index'],
                
                'questions' => [
                    'permission' => 'manage_questions',
                    'title' => 'پشتیبانی ها',
                    'icon' => 'question',
                    'route' => 'admin.questions.index',
                    'submenu' => [
                        'questions' => ['permission' => 'manage_questions', 'title' => 'مشاهده', 'icon' => 'question', 'route' => 'admin.questions.index'],
                        'editQuestion' => ['permission' => '', 'title' => 'پشتیبانی جدید', 'icon' => 'pencil', 'route' => 'admin.questions.create'],
                        'catquest' => ['permission' => 'manage_catquest', 'title' => 'گروه بندی', 'icon' => 'bookmark', 'route' => 'admin.catquest.index'],
                    ]
                ],
                
                'setting' => ['permission' => 'change_settings', 'title' => 'تنظیمات', 'icon' => 'gears', 'route' => 'admin.setting.index'],
            ];
            
            $currentRoute = request()->route()->getName();
        @endphp
        
        @foreach($menus as $key => $menu)
            @php
                // بررسی دسترسی
                if (!empty($menu['permission']) && !auth()->user()->can($menu['permission'])) {
                    // اگر زیرمنو داره، بررسی کن حداقل یکی دسترسی داشته باشه
                    if (isset($menu['submenu'])) {
                        $hasAccess = false;
                        foreach ($menu['submenu'] as $sub) {
                            if (empty($sub['permission']) || auth()->user()->can($sub['permission'])) {
                                $hasAccess = true;
                                break;
                            }
                        }
                        if (!$hasAccess) continue;
                    } else {
                        continue;
                    }
                }
                
                $isActive = str_starts_with($currentRoute, 'admin.' . $key);
                $hasSubmenu = isset($menu['submenu']);
                $liClass = 'sidebar-item item-' . $key;
                if ($hasSubmenu) $liClass .= ' has-menu';
            @endphp
            
            <li class="{{ $liClass }}" style="color:#0BB0E7">
                <span class="option {{ $isActive ? 'this-page' : '' }}">
                    <a href="{{ $hasSubmenu ? '#' : route($menu['route']) }}">
                        <i class="fa fa-{{ $menu['icon'] }}"></i>
                        <span class="name">{{ $menu['title'] }}</span>
                        
                        @if($hasSubmenu)
                            <i class="fa fa-angle-double-down fa-lg toggle-sub-menu"></i>
                    </a>
                </span>
                
                <ul>
                    @foreach($menu['submenu'] as $subKey => $submenu)
                        @if(empty($submenu['permission']) || auth()->user()->can($submenu['permission']))
                            @php
                                $isSubActive = $currentRoute === $submenu['route'];
                            @endphp
                            <li>
                                <span class="option {{ $isSubActive ? 'this-page' : '' }}">
                                    <a href="{{ route($submenu['route']) }}">
                                        <i class="fa fa-{{ $submenu['icon'] }} {{ $isSubActive ? 'this-page' : '' }}"></i>
                                        <span class="name">{{ $submenu['title'] }}</span>
                                    </a>
                                </span>
                            </li>
                        @endif
                    @endforeach
                </ul>
                
                @else
                    </a>
                </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>

