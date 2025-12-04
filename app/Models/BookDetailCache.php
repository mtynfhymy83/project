<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookDetailCache extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'book_id';
    protected $table = 'book_detail_cache';

    protected $fillable = [
        'book_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Relations
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Update cache for a book
     */
    public static function updateForBook(Book $book): self
    {
        $payload = [
            'id' => $book->id,
            'title' => $book->title,
            'slug' => $book->slug,
            'excerpt' => $book->excerpt,
            'cover_image' => $book->cover_url,
            'thumbnail' => $book->thumbnail_url,
            'price' => $book->price,
            'is_free' => $book->is_free,
            'pages' => $book->pages,
            'status' => $book->status,
            'authors' => $book->authors_cache,
            'categories' => $book->categories_cache,
            'stats' => [
                'view_count' => $book->stats->view_count ?? 0,
                'purchase_count' => $book->stats->purchase_count ?? 0,
                'rating' => $book->stats->rating ?? 0,
                'rating_count' => $book->stats->rating_count ?? 0,
            ],
        ];

        return static::updateOrCreate(
            ['book_id' => $book->id],
            [
                'payload' => $payload,
                'updated_at' => now(),
            ]
        );
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

