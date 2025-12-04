<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // فعال‌سازی extensionهای لازم (اگر قبلاً نصب نشده باشند)
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm;");
        DB::statement("CREATE EXTENSION IF NOT EXISTS unaccent;");

        Schema::create('ci_posts', function (Blueprint $table) {
            // Primary key (مطابق با id INT NOT NULL در MySQL - اما در PG از serial استفاده می‌کنیم)
            $table->bigIncrements('id');


            $table->string('title', 300)->nullable();

            // محتوای اصلی

            $table->text('excerpt')->nullable();

            $table->string('category')->nullable(); // در MySQL varchar(255)


            $table->string('thumb', 1000)->nullable();
            $table->string('icon', 30)->nullable();

            $table->integer('author')->nullable();


            // published tinyint(1)
            $table->boolean('published')->default(false);

            $table->integer('draft')->nullable();

            $table->string('date_modified', 50)->nullable();
            $table->timestampTz('date')->nullable();



            // specific fields for books
            $table->integer('size')->default(0)->comment('حجم کتاب');
            $table->integer('price')->default(0)->comment('قیمت');
            $table->integer('pages')->default(0)->comment('تعداد صفحه');
            $table->integer('part_count')->default(0)->comment('تعداد پاراگراف');

            // flags (همه به boolean تبدیل شدند)
            $table->boolean('has_description')->default(false)->comment('دارای شرح');
            $table->boolean('has_sound')->default(false)->comment('دارای صوت');
            $table->boolean('has_video')->default(false)->comment('دارای ویدئو');
            $table->boolean('has_image')->default(false)->comment('دارای تصویر');
            $table->boolean('has_test')->default(false)->comment('دارای آزمون تستی');
            $table->boolean('has_tashrihi')->default(false)->comment('دارای آزمون تشریحی');
            $table->boolean('has_download')->default(false)->comment('دانلود');

            $table->boolean('has_bought')->default(false);
            $table->boolean('has_membership')->default(false);

            // timestamps — اگر فایل اصلی زمان ایجاد/آپدیت نداشت، این دو ستون اضافه هستند
            $table->timestampsTz();

            // ایندکس‌های پایه‌ای برای فیلتر / مرتب‌سازی سریع
            $table->index('type', 'ci_posts_type_idx');
            $table->index('author', 'ci_posts_author_idx');
            $table->index('published', 'ci_posts_published_idx');
        });

        // === Full-text & Trigram indexes (مطابق درخواست تو) ===

        // Fulltext (tsvector) روی title
        DB::statement("CREATE INDEX ci_posts_title_fulltext_idx ON ci_posts USING gin(to_tsvector('english', coalesce(title, '')));");

        // Fulltext (tsvector) روی content
        DB::statement("CREATE INDEX ci_posts_content_fulltext_idx ON ci_posts USING gin(to_tsvector('english', coalesce(content, '')));");

        // Trigram extension already created above; حالا ایندکس trigram برای جستجوی فازی
        DB::statement("CREATE INDEX ci_posts_title_trgm_idx ON ci_posts USING gin (title gin_trgm_ops);");

        // اگر خواستی می‌تونیم trigram روی content هم بسازیم (هزینهٔ نوشتار بیشتر، ولی جستجوی فازی بهتر)
        DB::statement("CREATE INDEX ci_posts_content_trgm_idx ON ci_posts USING gin (content gin_trgm_ops);");

        // اگر خواستی میشه ستون tsvector جداگانه هم اضافه کرد و تریگر برای بروز رسانی خودکار ساخت.
        // مثال (در صورت نیاز):
        // DB::statement(\"ALTER TABLE ci_posts ADD COLUMN title_tsv tsvector;\");
        // DB::statement(\"UPDATE ci_posts SET title_tsv = to_tsvector('english', coalesce(title, ''));\");
        // DB::statement(\"CREATE INDEX ci_posts_title_tsv_idx ON ci_posts USING gin(title_tsv);\");
        // و سپس trigger برای بروز نگه داشتن title_tsv
    }

    public function down(): void
    {
        // حذف ایندکس‌های ساخته شده با DB::statement
        DB::statement('DROP INDEX IF EXISTS ci_posts_title_trgm_idx;');
        DB::statement('DROP INDEX IF EXISTS ci_posts_title_fulltext_idx;');
        DB::statement('DROP INDEX IF EXISTS ci_posts_content_fulltext_idx;');
        DB::statement('DROP INDEX IF EXISTS ci_posts_content_trgm_idx;');

        Schema::dropIfExists('books');
    }
};
