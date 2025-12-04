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
            $table->string('title', 300);
            $table->string('slug', 350)->unique();
            $table->text('excerpt')->nullable();
            $table->text('content')->nullable();
            $table->string('isbn', 20)->unique()->nullable();

            // Foreign Keys
            $table->unsignedBigInteger('publisher_id')->nullable();
            $table->unsignedBigInteger('primary_category_id')->nullable();

            // Media & Files
            $table->string('cover_image')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('icon', 50)->nullable();

            // Book Properties
            $table->integer('pages')->unsigned()->default(0);
            $table->bigInteger('file_size')->unsigned()->default(0);
            
            // Features as JSONB
            $table->jsonb('features')->default('{}');
            
            // Cache fields
            $table->jsonb('authors_cache')->default('[]');
            $table->jsonb('categories_cache')->default('[]');

            // Pricing
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->boolean('is_free')->default(false);

            // SEO
            $table->string('meta_keywords')->nullable();
            $table->string('meta_description')->nullable();
            $table->text('tags')->nullable();

            // Status
            $table->string('status', 30)->default('published');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('title');
            $table->index('status');
            $table->index('primary_category_id');
            $table->index('price');
            $table->index(['status', 'created_at']);
            $table->index(['primary_category_id', 'status']);
        });

        // Full-Text Search Indexes
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX books_title_fulltext_idx ON books USING gin(to_tsvector(\'english\', title))');
        DB::statement('CREATE INDEX books_content_fulltext_idx ON books USING gin(to_tsvector(\'english\', COALESCE(content, \'\')))');
        DB::statement('CREATE INDEX books_title_trgm_idx ON books USING gin(title gin_trgm_ops)');
        
        // GIN indexes for JSONB cache fields
        DB::statement('CREATE INDEX books_authors_cache_idx ON books USING gin(authors_cache)');
        DB::statement('CREATE INDEX books_categories_cache_idx ON books USING gin(categories_cache)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS books_title_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS books_content_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS books_title_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS books_authors_cache_idx');
        DB::statement('DROP INDEX IF EXISTS books_categories_cache_idx');
        Schema::dropIfExists('books');
    }
};
