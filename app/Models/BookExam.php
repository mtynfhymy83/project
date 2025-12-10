<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'title',
        'description',
        'duration_minutes',
        'passing_score',
        'total_score',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'total_score' => 'integer',
        'is_active' => 'boolean',
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
        return $this->belongsToMany(BookQuestion::class, 'exam_questions')
            ->withPivot('order', 'score')
            ->orderBy('exam_questions.order')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Helper Methods
     */
    public function getTotalQuestionsAttribute(): int
    {
        return $this->questions()->count();
    }
}






