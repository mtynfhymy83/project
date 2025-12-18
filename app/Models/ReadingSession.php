<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingSession extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['started_at', 'ended_at', 'created_at'];

    protected $fillable = [
        'user_id',
        'book_id',
        'started_at',
        'ended_at',
        'duration',
        'pages_read',
        'start_page',
        'end_page',
        'device_type',
        'platform',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'book_id' => 'integer',
        'duration' => 'integer',
        'pages_read' => 'integer',
        'start_page' => 'integer',
        'end_page' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForBook($query, int $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('ended_at');
    }

    /**
     * Accessors
     */
    public function getDurationMinutesAttribute(): int
    {
        return round($this->duration / 60);
    }

    public function getDurationHumanAttribute(): string
    {
        $minutes = $this->duration_minutes;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        }
        
        return "{$mins}m";
    }
}








