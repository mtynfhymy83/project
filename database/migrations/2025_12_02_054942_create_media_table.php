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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 100); // Polymorphic: App\Models\Book, etc.
            $table->unsignedBigInteger('model_id'); // Polymorphic ID
            $table->enum('type', ['audio', 'image', 'video', 'pdf']); // media type
            $table->enum('provider', ['s3', 'local', 'cdn', 'liara', 'minio'])->default('s3'); // storage provider
            $table->string('path', 1024); // Storage path
            $table->string('url', 1024)->nullable(); // Full URL (for CDN)
            $table->bigInteger('size')->unsigned()->nullable(); // File size in bytes
            $table->jsonb('metadata')->nullable(); // width, height, duration, bitrate, etc.
            $table->timestamps();

            // Indexes
            $table->index(['model_type', 'model_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};


