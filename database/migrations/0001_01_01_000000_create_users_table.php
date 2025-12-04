<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 15)->unique()->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->enum('level', ['user', 'admin', 'teacher'])->default('user');
            $table->enum('approved', ['yes', 'no'])->default('yes');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes برای بهینه‌سازی
            $table->index('username');
            $table->index('phone');
            $table->index('level');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
