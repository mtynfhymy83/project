<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookStats extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'book_id';

    protected $fillable = [
        'book_id',
        'view_count',
        'purchase_count',
        'download_count',
        'rating',
        'rating_count',
        'favorite_count',
        'comment_count',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'purchase_count' => 'integer',
        'download_count' => 'integer',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'favorite_count' => 'integer',
        'comment_count' => 'integer',
    ];

    /**
     * Relations
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Helper Methods
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
        $this->touch('updated_at');
    }

    public function incrementPurchases(): void
    {
        $this->increment('purchase_count');
        $this->touch('updated_at');
    }

    public function incrementDownloads(): void
    {
        $this->increment('download_count');
        $this->touch('updated_at');
    }

    public function incrementFavorites(): void
    {
        $this->increment('favorite_count');
        $this->touch('updated_at');
    }

    public function updateRating(float $newRating): void
    {
        $totalRating = ($this->rating * $this->rating_count) + $newRating;
        $newCount = $this->rating_count + 1;

        $this->update([
            'rating' => round($totalRating / $newCount, 2),
            'rating_count' => $newCount,
            'updated_at' => now(),
        ]);
    }

    /**
     * Get popularity score
     */
    public function getPopularityScoreAttribute(): float
    {
        return ($this->view_count * 0.3) + ($this->rating * 20) + ($this->purchase_count * 10);
    }

    /**
     * Touch updated_at
     */
    public function touch($attribute = null): bool
    {
        $this->updated_at = now();
        return $this->save();
    }
}








