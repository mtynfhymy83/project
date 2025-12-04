<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'version',
        'format',
        'path',
        'size',
        'duration_seconds',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'duration_seconds' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    /**
     * Accessors
     */
    public function getDownloadUrlAttribute(): string
    {
        // برای فایل‌های کتاب، از signed URL استفاده کن
        return app(\App\Services\MediaService::class)
            ->temporaryUrl($this->path, 60);
    }

    public function getStreamUrlAttribute(): ?string
    {
        if ($this->format !== 'audio') {
            return null;
        }
        
        return app(\App\Services\MediaService::class)->url($this->path);
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

