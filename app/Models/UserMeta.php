<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    use HasFactory;

    protected $table = 'user_meta';

    protected $fillable = [
        'user_id',
        'eitaa_id',
        'username',
        'first_name',
        'last_name',
        'preferences',
        'extra_data',
    ];

    protected $casts = [
        'preferences' => 'array',
        'extra_data' => 'array',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper Methods
     */

    /**
     * دریافت نام کامل
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * بروزرسانی Meta Data
     */
    public function updateMetaData(array $data): bool
    {
        return $this->update([
            'eitaa_id' => $data['eitaa_id'] ?? $this->eitaa_id,
            'username' => $data['username'] ?? $this->username,
            'first_name' => $data['first_name'] ?? $this->first_name,
            'last_name' => $data['last_name'] ?? $this->last_name,
        ]);
    }
}
