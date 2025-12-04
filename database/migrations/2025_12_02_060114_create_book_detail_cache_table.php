<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_detail_cache', function (Blueprint $table) {
            $table->foreignId('book_id')->primary()->constrained('books')->onDelete('cascade');
            $table->jsonb('payload'); // All data needed for API response
            $table->timestamp('updated_at')->nullable();

            // Index for sorting by update time
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_detail_cache');
    }
};


