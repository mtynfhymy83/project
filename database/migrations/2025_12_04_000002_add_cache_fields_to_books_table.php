<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Cache fields for fast reading (optional, for performance)
            $table->jsonb('authors_cache')->default('[]')->after('features');
            $table->jsonb('categories_cache')->default('[]')->after('authors_cache');
        });

        // GIN indexes for fast JSON querying (PostgreSQL)
        DB::statement('CREATE INDEX books_authors_cache_idx ON books USING gin(authors_cache)');
        DB::statement('CREATE INDEX books_categories_cache_idx ON books USING gin(categories_cache)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS books_authors_cache_idx');
        DB::statement('DROP INDEX IF EXISTS books_categories_cache_idx');
        
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['authors_cache', 'categories_cache']);
        });
    }
};

