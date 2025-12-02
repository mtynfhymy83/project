<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300)->index();
            $table->string('slug', 350)->unique();
            $table->text('excerpt')->nullable(); // توضیحات کوتاه
            $table->text('content')->nullable(); // توضیحات کامل
            $table->string('isbn', 20)->unique()->nullable();

            // Foreign Keys (will be added in a separate migration after dependent tables exist)
            $table->unsignedBigInteger('publisher_id')->nullable();
            $table->unsignedBigInteger('primary_category_id')->nullable();

            // Media & Files
            $table->string('cover_image')->nullable(); // تصویر جلد
            $table->string('thumbnail')->nullable(); // تصویر کوچک
            $table->string('icon', 50)->nullable(); // آیکون

            // Book Properties
            $table->integer('pages')->unsigned()->default(0);
            $table->integer('total_paragraphs')->unsigned()->default(0); // تعداد کل پاراگراف‌ها
            $table->bigInteger('file_size')->unsigned()->default(0); // حجم کل محتوا (byte)
            $table->integer('position')->default(0); // ترتیب نمایش

            // Features (بهینه شده با bit flags در آینده)
            $table->boolean('has_description')->default(false);
            $table->boolean('has_sound')->default(false);
            $table->boolean('has_video')->default(false);
            $table->boolean('has_image')->default(false);
            $table->boolean('has_test')->default(false); // تست
            $table->boolean('has_essay')->default(false); // تشریحی
            $table->boolean('has_download')->default(false);

            // Pricing
            $table->decimal('price', 12, 2)->default(0); // قیمت خرید مستقیم
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->boolean('is_free')->default(false);

            // SEO
            $table->string('meta_keywords')->nullable();
            $table->string('meta_description')->nullable();
            $table->text('tags')->nullable(); // JSON array

            // Status
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->boolean('is_special')->default(false); // کتاب ویژه
            $table->boolean('allow_comments')->default(true);

            // Statistics
            $table->integer('view_count')->unsigned()->default(0);
            $table->integer('purchase_count')->unsigned()->default(0);
            $table->integer('download_count')->unsigned()->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->unsigned()->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for Performance
            $table->index('status');
            $table->index('is_special');
            $table->index('primary_category_id');
            $table->index('price');
            $table->index('position');
            $table->index('rating');
            $table->index(['status', 'created_at']);
            $table->index(['status', 'rating']);
            $table->index(['primary_category_id', 'status']);
        });

        // Full-Text Search Index (PostgreSQL)
        DB::statement('CREATE INDEX books_title_fulltext_idx ON books USING gin(to_tsvector(\'english\', title))');
        DB::statement('CREATE INDEX books_content_fulltext_idx ON books USING gin(to_tsvector(\'english\', COALESCE(content, \'\')))');
// Trigram Index for Better Partial Matching (برای جستجوی فازی)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX books_title_trgm_idx ON books USING gin(title gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS books_title_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS books_content_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS books_title_trgm_idx');
        Schema::dropIfExists('books');
    }
};
