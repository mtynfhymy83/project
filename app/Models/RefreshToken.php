<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'access_token_id',
        'token',
        'expires_at',
        'device_name',
        'ip_address',
        'is_used',
        'is_revoked',
        'used_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_used' => 'boolean',
        'is_revoked' => 'boolean',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accessToken()
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * Helper Methods
     */

    /**
     * تولید توکن رندوم
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(60));
    }

    /**
     * آیا توکن منقضی شده؟
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * آیا توکن معتبر است؟
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->is_revoked && !$this->isExpired();
    }

    /**
     * علامت‌گذاری به عنوان استفاده شده
     */
    public function markAsUsed(): bool
    {
        return $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * لغو توکن
     */
    public function revoke(): bool
    {
        return $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
        ]);
    }

    /**
     * Scopes
     */

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('is_revoked', false)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // تولید خودکار توکن
        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = static::generateToken();
            }
        });
    }

    /**
     * Auto-delete expired tokens
     */
    public static function deleteExpiredTokens(): int
    {
        return static::where('expires_at', '<=', now()->subDays(30))->delete();
    }
}
