<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BookContent extends Model
{
    use HasFactory;

    protected $table = 'book_contents';

    protected $fillable = [
        'book_id',
        'page_number',
        'paragraph_number',
        'order',
        'text',
        'description',
        'sound_path',
        'image_paths',
        'video_path',
        'is_index',
        'index_title',
        'index_level',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'paragraph_number' => 'integer',
        'order' => 'integer',
        'is_index' => 'boolean',
        'index_level' => 'integer',
        'image_paths' => 'array',
    ];

    /**
     * Relations
     */

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function questions()
    {
        return $this->hasMany(BookQuestion::class, 'content_id');
    }

    /**
     * Helper Methods
     */

    public function getSoundUrl(): ?string
    {
        return $this->sound_path ? config('services.s3.url') . '/' . $this->sound_path : null;
    }

    public function getVideoUrl(): ?string
    {
        return $this->video_path ? config('services.s3.url') . '/' . $this->video_path : null;
    }

    public function getImageUrls(): array
    {
        if (empty($this->image_paths)) {
            return [];
        }

        return array_map(function ($path) {
            return config('services.s3.url') . '/' . $path;
        }, $this->image_paths);
    }

    public function hasMedia(): bool
    {
        return !empty($this->sound_path)
            || !empty($this->video_path)
            || !empty($this->image_paths);
    }

    /**
     * Scopes
     */

    public function scopeByPage(Builder $query, int $pageNumber): Builder
    {
        return $query->where('page_number', $pageNumber)
            ->orderBy('paragraph_number');
    }

    public function scopeByPageRange(Builder $query, int $startPage, int $endPage): Builder
    {
        return $query->whereBetween('page_number', [$startPage, $endPage])
            ->orderBy('page_number')
            ->orderBy('paragraph_number');
    }

    public function scopeIndexOnly(Builder $query): Builder
    {
        return $query->where('is_index', true)
            ->orderBy('page_number')
            ->orderBy('order');
    }

    public function scopeWithMedia(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('sound_path')
                ->orWhereNotNull('video_path')
                ->orWhereNotNull('image_paths');
        });
    }

    /**
     * Static Methods
     */

    public static function getBookIndex(int $bookId): array
    {
        return static::where('book_id', $bookId)
            ->where('is_index', true)
            ->orderBy('page_number')
            ->orderBy('order')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->index_title,
                    'level' => $item->index_level,
                    'page' => $item->page_number,
                ];
            })
            ->toArray();
    }

    public static function getPageContent(int $bookId, int $pageNumber): array
    {
        return static::where('book_id', $bookId)
            ->where('page_number', $pageNumber)
            ->orderBy('paragraph_number')
            ->get()
            ->map(function ($content) {
                return [
                    'id' => $content->id,
                    'paragraph' => $content->paragraph_number,
                    'text' => $content->text,
                    'description' => $content->description,
                    'sound_url' => $content->getSoundUrl(),
                    'video_url' => $content->getVideoUrl(),
                    'image_urls' => $content->getImageUrls(),
                    'is_index' => $content->is_index,
                    'index_title' => $content->index_title,
                ];
            })
            ->toArray();
    }
}

