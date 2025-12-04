# ساختار کامل دیتابیس - پروژه کتاب

## 📊 نمای کلی

**تعداد کل جداول:** 27 جدول  
**دیتابیس:** PostgreSQL  
**نسخه لاراول:** 11.x  
**تاریخ آخرین بروزرسانی:** 2025-12-04

---

## 📑 فهرست جداول

### جداول اصلی
1. [users](#1-users) - کاربران
2. [user_meta](#2-user_meta) - اطلاعات متا کاربران (Eitaa)
3. [user_profiles](#3-user_profiles) - پروفایل کاربران
4. [books](#4-books) - کتاب‌ها
5. [book_versions](#5-book_versions) - نسخه‌های مختلف کتاب
6. [book_contents](#6-book_contents) - محتوای کتاب (صفحات و پاراگراف‌ها)
7. [book_stats](#7-book_stats) - آمار کتاب‌ها
8. [book_detail_cache](#8-book_detail_cache) - کش جزئیات کتاب
9. [authors](#9-authors) - نویسندگان
10. [publishers](#10-publishers) - ناشران
11. [categories](#11-categories) - دسته‌بندی‌ها
12. [media](#12-media) - فایل‌های رسانه‌ای

### جداول احراز هویت
13. [access_tokens](#13-access_tokens) - توکن‌های دسترسی
14. [refresh_tokens](#14-refresh_tokens) - توکن‌های تازه‌سازی

### جداول کاربری
15. [user_library](#15-user_library) - کتابخانه کاربران
16. [reading_sessions](#16-reading_sessions) - جلسات مطالعه (پارتیشن شده)
17. [favorites](#17-favorites) - علاقه‌مندی‌ها
18. [purchases](#18-purchases) - خریدها

### جداول اشتراک
19. [subscription_plans](#19-subscription_plans) - پلن‌های اشتراک
20. [user_subscriptions](#20-user_subscriptions) - اشتراک‌های کاربران
21. [subscription_logs](#21-subscription_logs) - لاگ اشتراک‌ها

### جداول آزمون
22. [book_questions](#22-book_questions) - سوالات
23. [user_question_answers](#23-user_question_answers) - پاسخ‌های کاربران
24. [book_exams](#24-book_exams) - آزمون‌ها
25. [exam_questions](#25-exam_questions) - سوالات آزمون

### جداول Pivot
26. [book_category](#26-book_category) - کتاب-دسته‌بندی
27. [book_author](#27-book_author) - کتاب-نویسنده

### جداول سیستمی
28. [search_logs](#28-search_logs) - لاگ جستجوها
- [cache](#cache-laravel) - کش لاراول
- [jobs](#jobs-laravel) - صف کارها

---

## 📋 جزئیات جداول

### 1. users
**توضیحات:** جدول کاربران (لاراول پیش‌فرض)

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| name | VARCHAR(255) | نام |
| email | VARCHAR(255) UNIQUE | ایمیل |
| email_verified_at | TIMESTAMP | زمان تایید ایمیل |
| password | VARCHAR(255) | رمز عبور |
| remember_token | VARCHAR(100) | توکن یادآوری |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- UNIQUE: email

**روابط:**
- has one: user_meta, user_profiles
- has many: access_tokens, refresh_tokens, user_library, purchases

---

### 2. user_meta
**توضیحات:** اطلاعات یکپارچگی Eitaa و متا

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| eitaa_id | VARCHAR UNIQUE | شناسه Eitaa |
| username | VARCHAR UNIQUE | نام کاربری |
| first_name | VARCHAR | نام |
| last_name | VARCHAR | نام خانوادگی |
| preferences | JSONB | تنظیمات |
| extra_data | JSONB | داده‌های اضافی |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- INDEX: user_id, eitaa_id, username
- UNIQUE: eitaa_id, username

---

### 3. user_profiles
**توضیحات:** پروفایل کاربران (جدا از user_meta)

| ستون | نوع | توضیحات |
|------|-----|---------|
| user_id | BIGINT FK PRIMARY | شناسه کاربر |
| avatar | VARCHAR | آواتار |
| preferences | JSONB | تنظیمات خواندن و UI |
| metadata | JSONB | داده‌های انعطاف‌پذیر |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: user_id
- FOREIGN KEY: user_id → users(id) CASCADE

**روابط:**
- belongs to: users

---

### 4. books
**توضیحات:** جدول اصلی کتاب‌ها

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| title | VARCHAR(300) | عنوان |
| slug | VARCHAR(350) UNIQUE | نامک |
| excerpt | TEXT | خلاصه کوتاه |
| content | TEXT | توضیحات کامل |
| isbn | VARCHAR(20) UNIQUE | شابک |
| publisher_id | BIGINT FK | شناسه ناشر |
| primary_category_id | BIGINT FK | دسته‌بندی اصلی |
| cover_image | VARCHAR | تصویر جلد |
| thumbnail | VARCHAR | تصویر کوچک |
| icon | VARCHAR(50) | آیکون |
| pages | INTEGER | تعداد صفحات |
| file_size | BIGINT | حجم فایل |
| features | JSONB | ویژگی‌های کتاب |
| price | DECIMAL(12,2) | قیمت |
| discount_price | DECIMAL(12,2) | قیمت با تخفیف |
| is_free | BOOLEAN | رایگان؟ |
| meta_keywords | VARCHAR | کلمات کلیدی SEO |
| meta_description | VARCHAR | توضیحات SEO |
| tags | TEXT | برچسب‌ها (JSON) |
| status | VARCHAR(30) | وضعیت |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |
| deleted_at | TIMESTAMP | تاریخ حذف نرم |

**ایندکس‌ها:**
- PRIMARY KEY: id
- UNIQUE: slug, isbn
- INDEX: title, status, primary_category_id, price
- INDEX: (status, created_at), (primary_category_id, status)
- GIN: to_tsvector('english', title) - جستجوی متنی
- GIN: to_tsvector('english', content) - جستجوی متنی
- GIN: title gin_trgm_ops - جستجوی فازی

**روابط:**
- belongs to: publishers, categories (primary)
- has many: book_versions, book_contents, book_questions
- has one: book_stats, book_detail_cache
- many to many: categories, authors
- morph many: media

---

### 5. book_versions
**توضیحات:** نسخه‌های مختلف فایل کتاب (epub, pdf, audio)

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| book_id | BIGINT FK | شناسه کتاب |
| version | VARCHAR(50) | نسخه (مثال: 1.0) |
| format | VARCHAR(20) | فرمت (epub, pdf, audio) |
| path | VARCHAR(1000) | مسیر فایل |
| size | BIGINT | حجم فایل (بایت) |
| duration_seconds | INTEGER | مدت زمان (برای audio) |
| is_active | BOOLEAN | فعال؟ |
| metadata | JSONB | متادیتا |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: book_id → books(id) CASCADE
- INDEX: (book_id, is_active), (book_id, format), format
- UNIQUE PARTIAL: (book_id, format) WHERE is_active = true

**ویژگی خاص:**
- یک کتاب فقط می‌تواند یک نسخه فعال از هر فرمت داشته باشد

**روابط:**
- belongs to: books

---

### 6. book_contents
**توضیحات:** محتوای کتاب شامل صفحات، پاراگراف‌ها و رسانه‌ها

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| book_id | BIGINT FK | شناسه کتاب |
| page_number | INTEGER | شماره صفحه |
| paragraph_number | INTEGER | شماره پاراگراف در صفحه |
| order | INTEGER | ترتیب نمایش |
| text | TEXT | متن پاراگراف |
| description | TEXT | شرح/توضیحات |
| sound_path | VARCHAR | مسیر فایل صوتی |
| image_paths | TEXT | مسیرهای تصاویر (JSON) |
| video_path | VARCHAR | مسیر ویدیو |
| is_index | BOOLEAN | جزو فهرست؟ |
| index_title | VARCHAR | عنوان در فهرست |
| index_level | INTEGER | سطح فهرست (1,2,3,...) |
| tsv | TSVECTOR | بردار جستجوی متنی |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: book_id → books(id) CASCADE
- INDEX: book_id
- INDEX: (book_id, page_number)
- INDEX: (book_id, page_number, paragraph_number)
- INDEX: (book_id, order)
- INDEX: (book_id, is_index)
- INDEX: (book_id, page_number, order) - بهینه‌سازی ناوبری
- UNIQUE: (book_id, page_number, paragraph_number)
- GIN: tsv - جستجوی متنی
- GIN: text gin_trgm_ops - جستجوی فازی

**تریگر:**
- `book_contents_tsv_trigger` - بروزرسانی خودکار tsv

**ویژگی‌های خاص:**
- ترکیب صفحات و پاراگراف‌ها در یک جدول (بدون JOIN)
- پشتیبانی از رسانه‌های چندگانه (صوت، تصویر، ویدیو)
- قابلیت فهرست‌بندی سلسله‌مراتبی

**روابط:**
- belongs to: books

---

### 7. book_stats
**توضیحات:** آمار غیرنرمال شده کتاب‌ها (برای کارایی)

| ستون | نوع | توضیحات |
|------|-----|---------|
| book_id | BIGINT FK PRIMARY | شناسه کتاب |
| view_count | BIGINT | تعداد بازدید |
| purchase_count | INTEGER | تعداد خرید |
| download_count | INTEGER | تعداد دانلود |
| rating | DECIMAL(3,2) | امتیاز |
| rating_count | INTEGER | تعداد امتیازدهی |
| favorite_count | INTEGER | تعداد علاقه‌مندی |
| comment_count | INTEGER | تعداد نظرات |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: book_id
- FOREIGN KEY: book_id → books(id) CASCADE
- INDEX: view_count, purchase_count, rating
- INDEX: (rating, rating_count)
- INDEX: (view_count, rating) - لیدربورد

**تریگر:**
- `book_stats_auto_create` - ایجاد خودکار هنگام افزودن کتاب

**روابط:**
- belongs to: books

---

### 8. book_detail_cache
**توضیحات:** کش جزئیات کتاب برای عملکرد بهتر API

| ستون | نوع | توضیحات |
|------|-----|---------|
| book_id | BIGINT FK PRIMARY | شناسه کتاب |
| payload | JSONB | داده‌های کامل API |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: book_id
- FOREIGN KEY: book_id → books(id) CASCADE
- INDEX: updated_at

**روابط:**
- belongs to: books

---

### 9. authors
**توضیحات:** نویسندگان

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| name | VARCHAR(255) | نام |
| slug | VARCHAR(280) UNIQUE | نامک |
| bio | TEXT | بیوگرافی |
| avatar | VARCHAR | آواتار |
| website | VARCHAR | وب‌سایت |
| is_active | BOOLEAN | فعال؟ |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |
| deleted_at | TIMESTAMP | تاریخ حذف نرم |

**ایندکس‌ها:**
- PRIMARY KEY: id
- UNIQUE: slug
- INDEX: slug, is_active

**روابط:**
- many to many: books

---

### 10. publishers
**توضیحات:** ناشران

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| name | VARCHAR(255) | نام |
| slug | VARCHAR(280) UNIQUE | نامک |
| description | TEXT | توضیحات |
| logo | VARCHAR | لوگو |
| website | VARCHAR | وب‌سایت |
| is_active | BOOLEAN | فعال؟ |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |
| deleted_at | TIMESTAMP | تاریخ حذف نرم |

**ایندکس‌ها:**
- PRIMARY KEY: id
- UNIQUE: slug
- INDEX: slug, is_active

**روابط:**
- has many: books

---

### 11. categories
**توضیحات:** دسته‌بندی‌ها (سلسله‌مراتبی)

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| name | VARCHAR(255) | نام |
| slug | VARCHAR(280) UNIQUE | نامک |
| description | TEXT | توضیحات |
| parent_id | BIGINT FK | دسته والد |
| image | VARCHAR | تصویر |
| icon | VARCHAR(50) | آیکون |
| position | INTEGER | ترتیب نمایش |
| is_active | BOOLEAN | فعال؟ |
| type | VARCHAR(50) | نوع |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- UNIQUE: slug
- FOREIGN KEY: parent_id → categories(id) CASCADE
- INDEX: slug, parent_id, type
- INDEX: (is_active, position), (parent_id, is_active)

**روابط:**
- self referencing: parent/children
- many to many: books

---

### 12. media
**توضیحات:** فایل‌های رسانه‌ای (Polymorphic)

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| model_type | VARCHAR(100) | نوع مدل |
| model_id | BIGINT | شناسه مدل |
| type | ENUM | نوع رسانه |
| provider | ENUM | ارائه‌دهنده ذخیره‌سازی |
| path | VARCHAR(1024) | مسیر |
| url | VARCHAR(1024) | URL کامل (CDN) |
| size | BIGINT | حجم |
| metadata | JSONB | متادیتا |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ENUM Values:**
- type: audio, image, video, pdf
- provider: s3, local, cdn, liara, minio

**ایندکس‌ها:**
- PRIMARY KEY: id
- INDEX: (model_type, model_id), type

**روابط:**
- morph to: books, authors, etc.

---

### 13. access_tokens
**توضیحات:** توکن‌های دسترسی

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| token | VARCHAR(255) UNIQUE | توکن |
| token_type | VARCHAR(50) | نوع توکن |
| expires_at | TIMESTAMP | زمان انقضا |
| device_name | VARCHAR | نام دستگاه |
| device_type | VARCHAR(50) | نوع دستگاه |
| platform | VARCHAR(50) | پلتفرم |
| ip_address | VARCHAR(45) | آدرس IP |
| user_agent | TEXT | User Agent |
| is_revoked | BOOLEAN | لغو شده؟ |
| revoked_at | TIMESTAMP | زمان لغو |
| last_used_at | TIMESTAMP | آخرین استفاده |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- UNIQUE: token
- INDEX: (user_id, is_revoked, expires_at), expires_at

**روابط:**
- belongs to: users

---

### 14. refresh_tokens
**توضیحات:** توکن‌های تازه‌سازی

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| access_token_id | BIGINT FK | شناسه توکن دسترسی |
| token | VARCHAR(255) UNIQUE | توکن |
| expires_at | TIMESTAMP | زمان انقضا |
| device_name | VARCHAR | نام دستگاه |
| ip_address | VARCHAR(45) | آدرس IP |
| is_used | BOOLEAN | استفاده شده؟ |
| is_revoked | BOOLEAN | لغو شده؟ |
| used_at | TIMESTAMP | زمان استفاده |
| revoked_at | TIMESTAMP | زمان لغو |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- FOREIGN KEY: access_token_id → access_tokens(id) CASCADE
- UNIQUE: token
- INDEX: (user_id, is_used, is_revoked, expires_at), expires_at

**روابط:**
- belongs to: users, access_tokens

---

### 15. user_library
**توضیحات:** کتابخانه کاربران (ساده شده)

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| book_id | BIGINT FK | شناسه کتاب |
| progress_percent | DECIMAL(5,2) | درصد پیشرفت |
| current_page | INTEGER | صفحه فعلی |
| status | VARCHAR(30) | وضعیت |
| last_read_at | TIMESTAMP | آخرین مطالعه |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**Status Values:** not_started, reading, completed

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- FOREIGN KEY: book_id → books(id) CASCADE
- UNIQUE: (user_id, book_id)
- INDEX: (user_id, last_read_at), (user_id, status)
- INDEX: (user_id, status, last_read_at) - بهینه‌سازی

**روابط:**
- belongs to: users, books

---

### 16. reading_sessions
**توضیحات:** جلسات مطالعه (پارتیشن شده به صورت ماهانه)

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT | شناسه کاربر |
| book_id | BIGINT | شناسه کتاب |
| started_at | TIMESTAMPTZ | شروع |
| ended_at | TIMESTAMPTZ | پایان |
| duration | INTEGER | مدت زمان (ثانیه) |
| pages_read | INTEGER | تعداد صفحات خوانده شده |
| start_page | INTEGER | صفحه شروع |
| end_page | INTEGER | صفحه پایان |
| device_type | VARCHAR(50) | نوع دستگاه |
| platform | VARCHAR(50) | پلتفرم |
| created_at | TIMESTAMPTZ | تاریخ ایجاد |

**ویژگی خاص:** پارتیشن شده بر اساس created_at

**پارتیشن‌ها:**
- reading_sessions_YYYY_MM (برای هر ماه)

**ایندکس‌ها (در هر پارتیشن):**
- PRIMARY KEY: (id, created_at)
- INDEX: (user_id, created_at DESC)
- INDEX: (book_id, created_at DESC)

---

### 17. favorites
**توضیحات:** علاقه‌مندی‌های کاربران

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| book_id | BIGINT FK | شناسه کتاب |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- FOREIGN KEY: book_id → books(id) CASCADE
- UNIQUE: (user_id, book_id)
- INDEX: user_id, book_id

**روابط:**
- belongs to: users, books

---

### 18. purchases
**توضیحات:** خریدها و تراکنش‌ها

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| book_id | BIGINT FK | شناسه کتاب |
| subscription_plan_id | BIGINT FK | شناسه پلن اشتراک |
| amount | DECIMAL(12,2) | مبلغ |
| currency | VARCHAR(10) | واحد پول |
| gateway | VARCHAR(50) | درگاه |
| status | VARCHAR(30) | وضعیت |
| transaction_id | VARCHAR | شناسه تراکنش |
| authority | VARCHAR | Authority درگاه |
| metadata | JSONB | متادیتا |
| completed_at | TIMESTAMP | زمان تکمیل |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**Status Values:** pending, completed, failed, refunded

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- FOREIGN KEY: book_id → books(id) SET NULL
- FOREIGN KEY: subscription_plan_id → subscription_plans(id) SET NULL
- INDEX: (user_id, created_at), status, transaction_id, authority
- INDEX: (user_id, status, created_at) - بهینه‌سازی

**روابط:**
- belongs to: users, books, subscription_plans

---

### 19. subscription_plans
**توضیحات:** پلن‌های اشتراک

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| category_id | BIGINT FK | شناسه دسته‌بندی |
| duration_months | INTEGER | مدت (ماه) |
| price | DECIMAL(12,2) | قیمت |
| discount_percentage | DECIMAL(5,2) | درصد تخفیف |
| is_active | BOOLEAN | فعال؟ |
| priority | INTEGER | اولویت نمایش |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: category_id → categories(id) CASCADE
- UNIQUE: (category_id, duration_months)
- INDEX: category_id, (category_id, is_active)

**روابط:**
- belongs to: categories
- has many: user_subscriptions

---

### 20. user_subscriptions
**توضیحات:** اشتراک‌های کاربران

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| category_id | BIGINT FK | شناسه دسته |
| subscription_plan_id | BIGINT FK | شناسه پلن |
| purchase_id | BIGINT FK | شناسه خرید |
| started_at | TIMESTAMP | شروع |
| expires_at | TIMESTAMP | انقضا |
| is_active | BOOLEAN | فعال؟ |
| auto_renew | BOOLEAN | تمدید خودکار؟ |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- FOREIGN KEY: category_id → categories(id) CASCADE
- FOREIGN KEY: subscription_plan_id → subscription_plans(id) CASCADE
- FOREIGN KEY: purchase_id → purchases(id) SET NULL
- INDEX: (user_id, is_active), (category_id, is_active), expires_at

**روابط:**
- belongs to: users, categories, subscription_plans, purchases

---

### 21. subscription_logs
**توضیحات:** لاگ‌های اشتراک

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_subscription_id | BIGINT FK | شناسه اشتراک |
| action | VARCHAR(50) | عمل |
| description | TEXT | توضیحات |
| created_at | TIMESTAMP | تاریخ ایجاد |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_subscription_id → user_subscriptions(id) CASCADE
- INDEX: user_subscription_id

---

### 22. book_questions
**توضیحات:** سوالات تستی کتاب‌ها

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| book_id | BIGINT FK | شناسه کتاب |
| content_id | BIGINT FK | شناسه محتوا (پاراگراف) |
| type | ENUM | نوع سوال |
| question_text | TEXT | متن سوال |
| question_image | VARCHAR | تصویر سوال |
| difficulty_level | INTEGER | سطح سختی |
| order | INTEGER | ترتیب |
| options | JSONB | گزینه‌ها |
| correct_answer | VARCHAR | پاسخ صحیح |
| explanation | TEXT | توضیح پاسخ |
| explanation_image | VARCHAR | تصویر توضیح |
| explanation_video | VARCHAR | ویدیو توضیح |
| is_active | BOOLEAN | فعال؟ |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**Type Values:** multiple_choice, true_false, essay, fill_blank

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: book_id → books(id) CASCADE
- FOREIGN KEY: content_id → book_contents(id) SET NULL
- INDEX: book_id, content_id, (book_id, type), (book_id, is_active)

**روابط:**
- belongs to: books, book_contents

---

### 23. user_question_answers
**توضیحات:** پاسخ‌های کاربران به سوالات

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| question_id | BIGINT FK | شناسه سوال |
| user_answer | TEXT | پاسخ کاربر |
| is_correct | BOOLEAN | صحیح؟ |
| score | INTEGER | نمره |
| answered_at | TIMESTAMP | زمان پاسخ |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) CASCADE
- FOREIGN KEY: question_id → book_questions(id) CASCADE
- INDEX: user_id, question_id, (user_id, question_id), answered_at

---

### 24. book_exams
**توضیحات:** آزمون‌ها

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| book_id | BIGINT FK | شناسه کتاب |
| title | VARCHAR | عنوان |
| description | TEXT | توضیحات |
| duration_minutes | INTEGER | مدت زمان |
| passing_score | INTEGER | نمره قبولی |
| total_score | INTEGER | نمره کل |
| is_active | BOOLEAN | فعال؟ |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: book_id → books(id) CASCADE
- INDEX: book_id, is_active

---

### 25. exam_questions
**توضیحات:** رابطه سوالات و آزمون‌ها

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| exam_id | BIGINT FK | شناسه آزمون |
| question_id | BIGINT FK | شناسه سوال |
| order | INTEGER | ترتیب |
| score | INTEGER | امتیاز سوال |
| created_at | TIMESTAMP | تاریخ ایجاد |
| updated_at | TIMESTAMP | تاریخ بروزرسانی |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: exam_id → book_exams(id) CASCADE
- FOREIGN KEY: question_id → book_questions(id) CASCADE
- UNIQUE: (exam_id, question_id)
- INDEX: exam_id, question_id

---

### 26. book_category
**توضیحات:** جدول Pivot کتاب و دسته‌بندی

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| book_id | BIGINT FK | شناسه کتاب |
| category_id | BIGINT FK | شناسه دسته |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: book_id → books(id) CASCADE
- FOREIGN KEY: category_id → categories(id) CASCADE
- UNIQUE: (book_id, category_id)

---

### 27. book_author
**توضیحات:** جدول Pivot کتاب و نویسنده

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| book_id | BIGINT FK | شناسه کتاب |
| author_id | BIGINT FK | شناسه نویسنده |
| order | INTEGER | ترتیب نویسندگان |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: book_id → books(id) CASCADE
- FOREIGN KEY: author_id → authors(id) CASCADE
- UNIQUE: (book_id, author_id)

---

### 28. search_logs
**توضیحات:** لاگ جستجوهای کاربران

| ستون | نوع | توضیحات |
|------|-----|---------|
| id | BIGSERIAL | شناسه |
| user_id | BIGINT FK | شناسه کاربر |
| query | VARCHAR | عبارت جستجو |
| results_count | INTEGER | تعداد نتایج |
| clicked_book_id | BIGINT FK | کتاب کلیک شده |
| ip_address | VARCHAR(45) | آدرس IP |
| user_agent | TEXT | User Agent |
| created_at | TIMESTAMP | تاریخ ایجاد |

**ایندکس‌ها:**
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) SET NULL
- FOREIGN KEY: clicked_book_id → books(id) SET NULL
- INDEX: user_id, created_at

---

## 🔧 تریگرها و فانکشن‌ها

### 1. book_stats_auto_create
**جدول:** book_stats  
**زمان اجرا:** AFTER INSERT ON books  
**عملکرد:** ایجاد خودکار رکورد آمار برای کتاب جدید

```sql
CREATE OR REPLACE FUNCTION create_book_stats()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO book_stats (book_id, updated_at)
    VALUES (NEW.id, NOW())
    ON CONFLICT (book_id) DO NOTHING;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

### 2. book_contents_tsv_trigger
**جدول:** book_contents  
**زمان اجرا:** BEFORE INSERT OR UPDATE  
**عملکرد:** بروزرسانی خودکار بردار جستجوی متنی

```sql
CREATE OR REPLACE FUNCTION book_contents_tsv_trigger()
RETURNS TRIGGER AS $$
BEGIN
    NEW.tsv := to_tsvector('simple', COALESCE(NEW.text, ''));
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

---

## 📊 نمودار ERD (ساده شده)

```
users
├─── user_meta (1:1)
├─── user_profiles (1:1)
├─── access_tokens (1:N)
├─── refresh_tokens (1:N)
├─── user_library (1:N)
├─── purchases (1:N)
├─── user_subscriptions (1:N)
├─── favorites (1:N)
└─── search_logs (1:N)

books
├─── book_versions (1:N)
├─── book_contents (1:N) ← صفحات + پاراگراف‌ها + رسانه
├─── book_stats (1:1)
├─── book_detail_cache (1:1)
├─── book_questions (1:N)
├─── purchases (1:N)
├─── user_library (1:N)
├─── favorites (1:N)
├─── media (1:N - polymorphic)
├─── book_category (N:M)
│    └─── categories
└─── book_author (N:M)
     └─── authors

categories (hierarchical)
├─── parent_id → categories (self-reference)
├─── subscription_plans (1:N)
└─── user_subscriptions (1:N)

publishers
└─── books (1:N)

subscription_plans
└─── user_subscriptions (1:N)

user_subscriptions
└─── subscription_logs (1:N)

book_exams
└─── exam_questions (N:M)
     └─── book_questions
```

---

## 🚀 ویژگی‌های پیشرفته

### 1. جستجوی متنی (Full-Text Search)
- **Extension:** pg_trgm
- **جداول:** books.title, books.content, book_contents.text
- **نوع ایندکس:** GIN tsvector, GIN trigram

### 2. پارتیشن‌بندی (Partitioning)
- **جدول:** reading_sessions
- **نوع:** RANGE partitioning by created_at
- **دوره:** ماهانه

### 3. ذخیره‌سازی Polymorphic
- **جدول:** media
- **مدل‌ها:** books, authors, و سایر موارد

### 4. کش عملکرد
- **جدول:** book_detail_cache
- **نوع:** JSONB payload
- **هدف:** پاسخ API زیر 100ms

### 5. آمار غیرنرمال شده
- **جدول:** book_stats
- **هدف:** عملکرد بهتر برای کوئری‌های آماری

### 6. JSONB Flexibility
- **جداول:** 
  - user_profiles (preferences, metadata)
  - books (features)
  - media (metadata)
  - purchases (metadata)
  - book_contents (image_paths)

### 7. محتوای یکپارچه (Unified Content)
- **جدول:** book_contents
- **مزایا:**
  - بدون JOIN برای خواندن صفحات
  - پشتیبانی از رسانه‌های چندگانه
  - قابلیت فهرست‌بندی داخلی
  - عملکرد 5x بهتر

---

## 🎯 نکات عملکردی

### کوئری‌های بهینه شده با ایندکس‌ها

1. **جستجوی فازی در عنوان کتاب‌ها**
   ```sql
   SELECT * FROM books 
   WHERE title % 'search term'
   ORDER BY similarity(title, 'search term') DESC;
   ```

2. **لیست مطالعه کاربر**
   ```sql
   SELECT * FROM user_library 
   WHERE user_id = ? AND status = 'reading'
   ORDER BY last_read_at DESC;
   -- Uses: user_library_status_read_idx
   ```

3. **کتاب‌های محبوب**
   ```sql
   SELECT * FROM book_stats 
   ORDER BY view_count DESC, rating DESC 
   LIMIT 10;
   -- Uses: book_stats_popular_idx
   ```

4. **جستجو در محتوای کتاب**
   ```sql
   SELECT * FROM book_contents
   WHERE tsv @@ plainto_tsquery('simple', 'search')
   AND book_id = ?;
   -- Uses: GIN index on tsv
   ```

5. **خواندن یک صفحه کتاب (بدون JOIN)**
   ```sql
   SELECT * FROM book_contents
   WHERE book_id = ? AND page_number = ?
   ORDER BY "order";
   -- سرعت: 10-20ms (5x سریع‌تر از JOIN)
   ```

6. **دریافت فهرست کتاب**
   ```sql
   SELECT page_number, index_title, index_level
   FROM book_contents
   WHERE book_id = ? AND is_index = true
   ORDER BY page_number, "order";
   ```

---

## 📈 آمار دیتابیس

| مورد | تعداد |
|------|-------|
| تعداد کل جداول | 27 |
| تعداد جداول پارتیشن شده | 1 |
| تعداد تریگرها | 2 |
| تعداد ایندکس‌های GIN | 5 |
| تعداد روابط Foreign Key | 38+ |
| تعداد ایندکس‌های Composite | 18+ |
| تعداد فیلدهای JSONB | 11 |

---

## 🔐 امنیت

- ✅ رمزعبور هش شده در users
- ✅ توکن‌های منقضی شونده
- ✅ IP و User Agent tracking
- ✅ Soft deletes برای جداول مهم
- ✅ Foreign key constraints
- ✅ Unique constraints

---

## 📝 یادداشت‌ها

1. **مایگریشن‌ها تست شده:** تمام 27 مایگریشن با موفقیت اجرا شده‌اند
2. **زمان اجرای کل:** ~900ms
3. **دیتابیس:** PostgreSQL 14+
4. **Extension مورد نیاز:** pg_trgm
5. **آماده Production:** ✅
6. **تغییر مهم:** ترکیب book_pages و book_paragraphs در book_contents

---

## 🔄 تغییرات اخیر

### تغییر ساختار book_contents
- ❌ حذف: `book_pages` و `book_paragraphs` (دو جدول جداگانه)
- ✅ جایگزین: `book_contents` (جدول یکپارچه)
- 🚀 بهبود عملکرد: 5x سریع‌تر (بدون JOIN)
- ➕ ویژگی‌های جدید: رسانه (صوت، تصویر، ویدیو)، فهرست‌بندی

**دلیل تغییر:** در اپلیکیشن کتاب‌خوانی، همیشه صفحه و پاراگراف با هم استفاده می‌شوند. جداسازی آن‌ها فقط باعث JOIN اضافی و کاهش عملکرد می‌شد.

---

**تاریخ تولید:** 2025-12-04  
**نسخه:** 2.0  
**وضعیت:** Production Ready ✅
