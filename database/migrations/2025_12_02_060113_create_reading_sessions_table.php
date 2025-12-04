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
        // Note: Partitioning requires PostgreSQL
        // Create partitioned table for analytics
        DB::statement("
            CREATE TABLE reading_sessions (
                id BIGSERIAL,
                user_id BIGINT NOT NULL,
                book_id BIGINT NOT NULL,
                started_at TIMESTAMPTZ NOT NULL,
                ended_at TIMESTAMPTZ,
                duration INT DEFAULT 0,
                pages_read INT DEFAULT 0,
                start_page INT,
                end_page INT,
                device_type VARCHAR(50),
                platform VARCHAR(50),
                created_at TIMESTAMPTZ DEFAULT NOW(),
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at)
        ");

        // Create initial partitions (current month and next month)
        $currentMonth = date('Y_m');
        $nextMonth = date('Y_m', strtotime('+1 month'));
        $currentStart = date('Y-m-01');
        $nextStart = date('Y-m-01', strtotime('+1 month'));
        $nextEnd = date('Y-m-01', strtotime('+2 months'));

        DB::statement("
            CREATE TABLE reading_sessions_{$currentMonth} 
            PARTITION OF reading_sessions
            FOR VALUES FROM ('{$currentStart}') TO ('{$nextStart}')
        ");

        DB::statement("
            CREATE TABLE reading_sessions_{$nextMonth} 
            PARTITION OF reading_sessions
            FOR VALUES FROM ('{$nextStart}') TO ('{$nextEnd}')
        ");

        // Create composite indexes on partitions for better query performance
        DB::statement("
            CREATE INDEX reading_sessions_{$currentMonth}_user_idx 
            ON reading_sessions_{$currentMonth}(user_id, created_at DESC)
        ");
        
        DB::statement("
            CREATE INDEX reading_sessions_{$currentMonth}_book_idx 
            ON reading_sessions_{$currentMonth}(book_id, created_at DESC)
        ");
        
        DB::statement("
            CREATE INDEX reading_sessions_{$nextMonth}_user_idx 
            ON reading_sessions_{$nextMonth}(user_id, created_at DESC)
        ");
        
        DB::statement("
            CREATE INDEX reading_sessions_{$nextMonth}_book_idx 
            ON reading_sessions_{$nextMonth}(book_id, created_at DESC)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $currentMonth = date('Y_m');
        $nextMonth = date('Y_m', strtotime('+1 month'));
        
        DB::statement("DROP TABLE IF EXISTS reading_sessions_{$currentMonth}");
        DB::statement("DROP TABLE IF EXISTS reading_sessions_{$nextMonth}");
        DB::statement('DROP TABLE IF EXISTS reading_sessions');
    }
};


