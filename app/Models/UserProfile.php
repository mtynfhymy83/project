<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'eitaa_id',
        'username',
        'name',
        'family',
        'gender',
        'age',
        'national_code',
        'birthday',
        'city',
        'country',
        'postal_code',
        'adress',
        'code',
        'sendtime',
        'avatar',
        'preferences',
        'metadata',
    ];

    protected $casts = [
        'preferences' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessors
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        
        return app(\App\Services\MediaService::class)->url($this->avatar);
    }

    /**
     * Helper Methods
     */
    public function getPreference(string $key, $default = null)
    {
        return data_get($this->preferences, $key, $default);
    }

    public function setPreference(string $key, $value): void
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, $key, $value);
        $this->preferences = $preferences;
        $this->save();
    }
}





