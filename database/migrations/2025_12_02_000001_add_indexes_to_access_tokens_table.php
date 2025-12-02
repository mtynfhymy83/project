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
        // Use raw SQL to create indexes with IF NOT EXISTS for PostgreSQL
        DB::statement('CREATE INDEX IF NOT EXISTS access_tokens_token_index ON access_tokens (token)');
        DB::statement('CREATE INDEX IF NOT EXISTS access_tokens_token_user_id_index ON access_tokens (token, user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS access_tokens_user_id_index ON access_tokens (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS access_tokens_expires_at_index ON access_tokens (expires_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS access_tokens_is_revoked_index ON access_tokens (is_revoked)');
        
        DB::statement('CREATE INDEX IF NOT EXISTS refresh_tokens_token_index ON refresh_tokens (token)');
        DB::statement('CREATE INDEX IF NOT EXISTS refresh_tokens_user_id_index ON refresh_tokens (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS refresh_tokens_expires_at_index ON refresh_tokens (expires_at)');
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

