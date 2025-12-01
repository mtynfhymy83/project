<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['display_name', 'username'];

    /**
     * Get the identifier that will be stored in the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array for custom claims in JWT
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Display name attribute (for API responses)
     */
    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    /**
     * Username attribute (برای سازگاری با API قدیمی)
     */
    public function getUsernameAttribute()
    {
        return $this->userMeta?->username ?? 'user_' . $this->id;
    }

    /**
     * Relations
     */

    // User Meta (Eitaa ID, Username, etc.)
    public function userMeta()
    {
        return $this->hasOne(UserMeta::class);
    }

    // Access Tokens
    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    // Refresh Tokens
    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }

    // UTM Tracking
    public function utmRecords()
    {
        return $this->hasMany(UtmTracking::class);
    }

    // Purchases
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    // Library
    public function library()
    {
        return $this->hasMany(UserLibrary::class);
    }

    // Favorites
    public function favorites()
    {
        return $this->belongsToMany(Book::class, 'favorites')
            ->withTimestamps();
    }

    // Reviews
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Bookmarks
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // Reading Sessions
    public function readingSessions()
    {
        return $this->hasMany(ReadingSession::class);
    }

    /**
     * Helper Methods
     */

    /**
     * بروزرسانی زمان آخرین ورود
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * چک کردن وضعیت فعال بودن کاربر
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * دریافت Eitaa ID
     */
    public function getEitaaId(): ?string
    {
        return $this->userMeta?->eitaa_id;
    }

    /**
     * آیا کاربر کتاب خاصی را خریداری کرده؟
     */
    public function hasPurchased(int $bookId): bool
    {
        return $this->purchases()
            ->where('book_id', $bookId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Scopes
     */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
}
