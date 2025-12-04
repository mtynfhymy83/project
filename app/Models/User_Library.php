<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_Library extends Model
{
    use HasFactory;

    protected $table = 'user_library';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'book_id',
        'progress_percent',
        'current_page',
        'status',
        'last_read_at',
    ];

    protected $casts = [
        'progress_percent' => 'decimal:2',
        'current_page' => 'integer',
        'last_read_at' => 'datetime',
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
    public function scopeReading($query)
    {
        return $query->where('status', 'reading');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
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
