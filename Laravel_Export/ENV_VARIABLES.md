# 🔐 متغیرهای محیطی (Environment Variables)

این فایل شامل تمام متغیرهای محیطی مورد نیاز برای پنل ادمین است.

## 📝 نحوه استفاده

این متغیرها را به فایل `.env` پروژه Laravel خود اضافه کنید.

---

## ⚙️ تنظیمات پنل ادمین

```env
# عنوان پنل ادمین
ADMIN_TITLE="پنل مدیریت مدرس"

# مسیر لوگو
ADMIN_LOGO="/images/logo.png"

# مسیر فاویکون
ADMIN_FAVICON="/images/favicon.ico"
```

---

## 📊 تنظیمات Pagination

```env
# تعداد کاربران در هر صفحه
ADMIN_USERS_PER_PAGE=60

# تعداد پست‌ها در هر صفحه
ADMIN_POSTS_PER_PAGE=30

# تعداد نظرات در هر صفحه
ADMIN_COMMENTS_PER_PAGE=50
```

---

## 📤 تنظیمات آپلود فایل

```env
# حداکثر حجم آپلود (به کیلوبایت)
UPLOAD_MAX_SIZE=10240

# دیسک ذخیره‌سازی (public, s3, sftp)
FILESYSTEM_DISK=public
```

---

## 🔌 تنظیمات SFTP (اختیاری)

برای ذخیره‌سازی فایل‌ها روی سرور SFTP:

```env
SFTP_URL=https://louhnyrh.lexoyacloud.ir
SFTP_HOST=your-sftp-server.com
SFTP_PORT=22
SFTP_USERNAME=your-username
SFTP_PASSWORD=your-password
SFTP_ROOT=/
```

---

## 📨 تنظیمات SMS (اختیاری)

برای ارسال پیامک:

```env
# نوع سرویس (kavenegar, ghasedaksms, farazsms, etc)
SMS_PROVIDER=kavenegar

# کلید API
SMS_API_KEY=your-api-key

# شماره ارسال‌کننده
SMS_SENDER=10004346
```

### پروایدرهای پشتیبانی شده:
- Kavenegar
- Ghasedak SMS
- Faraz SMS
- IR Payamak
- Melipayamak

---

## 💳 تنظیمات درگاه پرداخت (اختیاری)

```env
# نوع درگاه (zarinpal, mellat, saman, parsian, etc)
PAYMENT_GATEWAY=zarinpal

# Zarinpal
ZARINPAL_MERCHANT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
ZARINPAL_CALLBACK_URL=http://your-domain.com/payment/callback
ZARINPAL_SANDBOX=false

# Mellat
MELLAT_TERMINAL_ID=
MELLAT_USERNAME=
MELLAT_PASSWORD=

# Saman
SAMAN_MERCHANT_ID=
```

---

## ☁️ تنظیمات AWS S3 (اختیاری)

برای ذخیره فایل‌ها در Amazon S3:

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=https://your-bucket.s3.amazonaws.com
```

---

## 🗄️ تنظیمات Redis (اختیاری)

برای بهبود عملکرد و Cache:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# استفاده از Redis برای Cache
CACHE_DRIVER=redis

# استفاده از Redis برای Session
SESSION_DRIVER=redis

# استفاده از Redis برای Queue
QUEUE_CONNECTION=redis
```

---

## 📧 تنظیمات ایمیل

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🔒 تنظیمات امنیتی

```env
# تعداد تلاش ناموفق لاگین قبل از قفل شدن
LOGIN_MAX_ATTEMPTS=5

# مدت زمان قفل شدن (به دقیقه)
LOGIN_LOCKOUT_MINUTES=15

# فعال‌سازی Two-Factor Authentication
TWO_FACTOR_ENABLED=false

# طول عمر Session (به دقیقه)
SESSION_LIFETIME=120
```

---

## 🌐 تنظیمات چندزبانگی

```env
# زبان پیش‌فرض
APP_LOCALE=fa

# زبان‌های قابل دسترس (با کاما جدا شوند)
AVAILABLE_LOCALES=fa,en,ar

# Timezone
APP_TIMEZONE=Asia/Tehran
```

---

## 🎨 تنظیمات ظاهری

```env
# تم رنگی (light, dark, auto)
ADMIN_THEME=light

# تعداد آیتم در منوی آخرین فعالیت‌ها
ADMIN_RECENT_ITEMS=10

# نمایش نوتیفیکیشن‌ها
ADMIN_NOTIFICATIONS_ENABLED=true
```

---

## 📊 تنظیمات آمار و گزارش

```env
# فعال‌سازی آمارگیری بازدید
ANALYTICS_ENABLED=true

# Google Analytics ID (اختیاری)
GOOGLE_ANALYTICS_ID=UA-XXXXXXXXX-X

# فعال‌سازی گزارش‌های خودکار
AUTO_REPORTS_ENABLED=true

# ایمیل دریافت‌کننده گزارش‌های روزانه
REPORTS_EMAIL=admin@yourdomain.com
```

---

## 🔍 تنظیمات جستجو

```env
# موتور جستجو (database, elasticsearch, meilisearch)
SEARCH_ENGINE=database

# ElasticSearch
ELASTICSEARCH_HOST=localhost
ELASTICSEARCH_PORT=9200

# MeiliSearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=masterKey
```

---

## 🚀 تنظیمات بهینه‌سازی

```env
# فعال‌سازی Cache
CACHE_ENABLED=true

# فعال‌سازی CDN
CDN_ENABLED=false
CDN_URL=https://cdn.yourdomain.com

# فشرده‌سازی تصاویر
IMAGE_COMPRESSION_ENABLED=true
IMAGE_COMPRESSION_QUALITY=85

# Lazy Loading تصاویر
IMAGE_LAZY_LOADING=true
```

---

## 🔔 تنظیمات نوتیفیکیشن

```env
# کانال‌های نوتیفیکیشن (database, mail, sms, pusher)
NOTIFICATION_CHANNELS=database,mail

# Pusher (برای نوتیفیکیشن Real-time)
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

---

## 📱 تنظیمات PWA (اختیاری)

```env
# فعال‌سازی Progressive Web App
PWA_ENABLED=false

# نام اپلیکیشن
PWA_NAME="${APP_NAME}"

# رنگ تم
PWA_THEME_COLOR=#4285f4

# رنگ پس‌زمینه
PWA_BACKGROUND_COLOR=#ffffff
```

---

## 🐛 تنظیمات Debug و Log

```env
# حالت Debug (فقط در محیط توسعه true کنید)
APP_DEBUG=true

# سطح Log (debug, info, notice, warning, error, critical, alert, emergency)
LOG_LEVEL=debug

# کانال Log (single, daily, slack, stack)
LOG_CHANNEL=stack

# تعداد روز نگهداری Log
LOG_MAX_FILES=14
```

---

## ✅ چک‌لیست تنظیمات

### ضروری
- [x] APP_NAME
- [x] APP_URL
- [x] DB_* (اطلاعات دیتابیس)
- [x] ADMIN_TITLE

### پیشنهادی
- [ ] Redis برای Cache
- [ ] SFTP یا S3 برای ذخیره فایل
- [ ] SMS Provider
- [ ] Payment Gateway

### اختیاری
- [ ] Mail Server
- [ ] Analytics
- [ ] PWA
- [ ] CDN

---

## 💡 نکات

1. **امنیت:** هیچ‌وقت فایل `.env` را commit نکنید
2. **Production:** در محیط production حتماً `APP_DEBUG=false` کنید
3. **Cache:** بعد از تغییر `.env` حتماً `php artisan config:cache` بزنید
4. **Backup:** از فایل `.env` خود نسخه پشتیبان بگیرید

---

**مثال فایل .env کامل:**

```env
APP_NAME="پنل مدیریت مدرس"
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=madras_db
DB_USERNAME=db_user
DB_PASSWORD=strong_password

ADMIN_TITLE="پنل مدیریت مدرس"
ADMIN_LOGO="/images/logo.png"
ADMIN_USERS_PER_PAGE=60
ADMIN_POSTS_PER_PAGE=30

REDIS_HOST=127.0.0.1
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

SMS_PROVIDER=kavenegar
SMS_API_KEY=your-api-key

PAYMENT_GATEWAY=zarinpal
ZARINPAL_MERCHANT_ID=your-merchant-id
```

