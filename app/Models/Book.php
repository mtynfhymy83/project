<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'isbn',
        'publisher_id',
        'primary_category_id',
        'cover_image',
        'thumbnail',
        'icon',
        'pages',
        'total_paragraphs',
        'file_size',
        'position',
        'has_description',
        'has_sound',
        'has_video',
        'has_image',
        'has_test',
        'has_essay',
        'has_download',
        'price',
        'discount_price',
        'is_free',
        'meta_keywords',
        'meta_description',
        'tags',
        'status',
        'is_special',
        'allow_comments',
        'view_count',
        'purchase_count',
        'download_count',
        'rating',
        'rating_count',
    ];

    protected $casts = [
        'pages' => 'integer',
        'total_paragraphs' => 'integer',
        'file_size' => 'integer',
        'position' => 'integer',
        'has_description' => 'boolean',
        'has_sound' => 'boolean',
        'has_video' => 'boolean',
        'has_image' => 'boolean',
        'has_test' => 'boolean',
        'has_essay' => 'boolean',
        'has_download' => 'boolean',
        'is_free' => 'boolean',
        'is_special' => 'boolean',
        'allow_comments' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'view_count' => 'integer',
        'purchase_count' => 'integer',
        'download_count' => 'integer',
        'rating_count' => 'integer',
    ];

    /**
     * Relations
     */

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function primaryCategory()
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_category')
            ->withTimestamps();
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_author')
            ->withPivot('order')
            ->orderBy('book_author.order')
            ->withTimestamps();
    }

    public function contents()
    {
        return $this->hasMany(BookContent::class)->orderBy('page_number')->orderBy('paragraph_number');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function questions()
    {
        return $this->hasMany(BookQuestion::class);
    }

    public function exams()
    {
        return $this->hasMany(BookExam::class);
    }

    /**
     * Helper Methods
     */

    public function getEffectivePrice(): float
    {
        return $this->discount_price ?? $this->price;
    }

    public function hasDiscount(): bool
    {
        return !is_null($this->discount_price) && $this->discount_price < $this->price;
    }

    public function getDiscountPercentage(): float
    {
        if (!$this->hasDiscount() || $this->price == 0) {
            return 0;
        }
        return round((($this->price - $this->discount_price) / $this->price) * 100, 2);
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function incrementPurchases(): void
    {
        $this->increment('purchase_count');
    }

    public function updateRating(float $newRating): void
    {
        $totalRating = ($this->rating * $this->rating_count) + $newRating;
        $newCount = $this->rating_count + 1;

        $this->update([
            'rating' => $totalRating / $newCount,
            'rating_count' => $newCount,
        ]);
    }
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_free', true);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_free', false);
    }

    public function scopeSpecial(Builder $query): Builder
    {
        return $query->where('is_special', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('primary_category_id', $categoryId)
            ->orWhereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereRaw("to_tsvector('english', title) @@ plainto_tsquery('english', ?)", [$term])
            ->orWhere('title', 'ILIKE', "%{$term}%");
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('purchase_count', 'desc')
            ->orderBy('rating', 'desc');
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->where('rating_count', '>', 0)
            ->orderBy('rating', 'desc')
            ->orderBy('rating_count', 'desc');
    }

    /**
     * Accessors
     */

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? asset('storage/' . $this->cover_image) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }

    public function getTagsArrayAttribute(): array
    {
        return $this->tags ? explode(',', $this->tags) : [];
    }

}

    /**
     * Scopes
     */
