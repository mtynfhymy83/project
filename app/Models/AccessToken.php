<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AccessToken extends Model
{
    use HasFactory;

    protected $table = 'access_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'token_type',
        'expires_at',
        'device_name',
        'device_type',
        'platform',
        'ip_address',
        'user_agent',
        'is_revoked',
        'revoked_at',
        'last_used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_revoked' => 'boolean',
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
        return !$this->is_revoked && !$this->isExpired();
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
     * بروزرسانی زمان آخرین استفاده
     */
    public function updateLastUsed(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * Scopes
     */

    public function scopeValid($query)
    {
        return $query->where('is_revoked', false)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    /**
     * Auto-delete expired tokens (برای Schedule)
     */
    public static function deleteExpiredTokens(): int
    {
        return static::expired()->delete();
    }
}

