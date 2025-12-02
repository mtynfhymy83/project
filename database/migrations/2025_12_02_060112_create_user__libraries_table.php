<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');

            // Access Type
            $table->enum('access_type', ['purchased', 'subscription', 'free'])->default('purchased');
            $table->unsignedBigInteger('purchase_id')->nullable(); // Foreign key will be added later
            $table->unsignedBigInteger('subscription_id')->nullable(); // Foreign key will be added later

            // Reading Progress
            $table->integer('current_page')->default(0);
            $table->integer('current_paragraph')->default(0);
            $table->integer('total_pages_read')->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->enum('status', ['not_started', 'reading', 'completed'])->default('not_started');

            // Timestamps
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('access_expires_at')->nullable(); // برای اشتراک

            // Reading Statistics
            $table->integer('total_reading_time')->default(0); // ثانیه
            $table->integer('session_count')->default(0); // تعداد جلسات مطالعه

            // Settings
            $table->boolean('needs_update')->default(false); // برای sync
            $table->json('reading_preferences')->nullable(); // تنظیمات خواندن (فونت، سایز، تم)

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('book_id');
            $table->index('access_type');
            $table->index('status');
            $table->index('last_read_at');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'last_read_at']);
            $table->index(['user_id', 'access_type']);
            $table->index('access_expires_at');

            // Unique: یک کتاب یکبار در کتابخانه
            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_library');
    }
};
