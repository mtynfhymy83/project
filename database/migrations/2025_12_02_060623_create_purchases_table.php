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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('book_id')->nullable()->constrained('books')->onDelete('set null');
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->onDelete('set null');
            
            // Payment details
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('IRR');
            $table->string('gateway', 50)->nullable(); // zarinpal, parsian, etc.
            $table->string('status', 30)->default('pending'); // pending, completed, failed, refunded
            $table->string('transaction_id')->nullable();
            $table->string('authority')->nullable(); // Gateway-specific reference
            
            // Additional metadata
            $table->jsonb('metadata')->nullable(); // Device info, IP, etc.
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('transaction_id');
            $table->index('authority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
