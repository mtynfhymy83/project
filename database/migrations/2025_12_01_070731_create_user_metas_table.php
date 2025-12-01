<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Eitaa Integration
            $table->string('eitaa_id')->unique()->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // Additional Meta
            $table->json('preferences')->nullable(); // تنظیمات شخصی کاربر
            $table->json('extra_data')->nullable(); // داده‌های اضافی

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('eitaa_id');
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_meta');
    }
};
