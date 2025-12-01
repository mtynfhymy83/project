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
        Schema::table('access_tokens', function (Blueprint $table) {
            // Index برای جستجوی سریع توکن
            $table->index('token', 'access_tokens_token_index');
            
            // Composite index برای جستجوی توکن با user_id
            $table->index(['token', 'user_id'], 'access_tokens_token_user_id_index');
            
            // Index برای user_id
            $table->index('user_id', 'access_tokens_user_id_index');
            
            // Index برای expires_at (برای پاک کردن توکن‌های منقضی شده)
            $table->index('expires_at', 'access_tokens_expires_at_index');
            
            // Index برای is_revoked
            $table->index('is_revoked', 'access_tokens_is_revoked_index');
        });
        
        Schema::table('refresh_tokens', function (Blueprint $table) {
            // Index برای token
            $table->index('token', 'refresh_tokens_token_index');
            
            // Index برای user_id
            $table->index('user_id', 'refresh_tokens_user_id_index');
            
            // Index برای expires_at
            $table->index('expires_at', 'refresh_tokens_expires_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_tokens', function (Blueprint $table) {
            $table->dropIndex('access_tokens_token_index');
            $table->dropIndex('access_tokens_token_user_id_index');
            $table->dropIndex('access_tokens_user_id_index');
            $table->dropIndex('access_tokens_expires_at_index');
            $table->dropIndex('access_tokens_is_revoked_index');
        });
        
        Schema::table('refresh_tokens', function (Blueprint $table) {
            $table->dropIndex('refresh_tokens_token_index');
            $table->dropIndex('refresh_tokens_user_id_index');
            $table->dropIndex('refresh_tokens_expires_at_index');
        });
    }
};

