<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'type',
        'provider',
        'path',
        'url',
        'size',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Polymorphic relation
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeAudios($query)
    {
        return $query->where('type', 'audio');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    public function scopePdfs($query)
    {
        return $query->where('type', 'pdf');
    }

    /**
     * Accessors
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
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

