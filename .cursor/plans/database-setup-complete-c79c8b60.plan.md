<!-- c79c8b60-5eba-4ed1-bda4-79a9156271bc 98a99cb6-dcb2-4513-8eca-e07f03939769 -->
# پلن کامل: Seeders، انتقال دیتا و Models

## مرحله 1: ساخت/تکمیل Models و Relations

### مدل‌های موجود که نیاز به بروزرسانی دارند:

- [`app/Models/Book.php`](app/Models/Book.php) - اضافه کردن relations جدید
- [`app/Models/Category.php`](app/Models/Category.php) - تکمیل شده
- [`app/Models/Author.php`](app/Models/Author.php) - تکمیل شده

### مدل‌های جدید که باید ساخته شوند:

1. `BookVersion` - نسخه‌های مختلف کتاب (epub, pdf, audio)
2. `BookStats` - آمار کتاب‌ها
3. `Media` - فایل‌های رسانه‌ای (polymorphic)
4. `ReadingSession` - جلسات مطالعه
5. `BookDetailCache` - کش جزئیات
6. `UserProfile` - پروفایل کاربران
7. `BookExam` - آزمون‌ها

## مرحله 2: ساخت Factories برای تست

Factory برای هر مدل اصلی:

- `BookFactory` - تولید کتاب‌های نمونه
- `AuthorFactory` - تولید نویسندگان
- `CategoryFactory` - تولید دسته‌بندی‌ها
- `BookContentFactory` - تولید محتوای نمونه
- `UserFactory` - کاربران (موجود است)

## مرحله 3: ساخت Seeders

1. `DatabaseSeeder` - seeder اصلی
2. `CategorySeeder` - دسته‌بندی‌های واقعی
3. `AuthorSeeder` - نویسندگان نمونه
4. `BookSeeder` - کتاب‌های کامل با relations
5. `BookContentSeeder` - محتوای نمونه کتاب‌ها
6. `UserSeeder` - کاربران تست

## مرحله 4: اسکریپت انتقال دیتا

1. ساخت Command: `MigrateOldDatabase`
2. Mapping فیلدهای قدیمی به جدید
3. Import به صورت Batch (برای عملکرد بهتر)
4. Validation و گزارش خطاها

**نیاز:** مشخص کردن مسیر SQL dump فایل

## مرحله 5: تست نهایی

- اجرای seeders
- بررسی relations
- تست کوئری‌های اصلی

### To-dos

- [ ] Create 7 new models with full relations
- [ ] Create factories for core models
- [ ] Create comprehensive seeders for testing
- [ ] Create data migration command from old database
- [ ] Test seeders and verify relations work