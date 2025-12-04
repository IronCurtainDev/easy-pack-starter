<?php

namespace EasyPack\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Default token expiry in days.
     */
    public const DEFAULT_EXPIRY_DAYS = 90;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'device_id',
        'device_type',
        'device_push_token',
        'latest_ip_address',
        'topic_subscriptions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'topic_subscriptions' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
        'abilities',
        'topic_subscriptions',
    ];

    /**
     * The attributes that should be visible for serialization.
     * Used for API responses and Swagger documentation.
     *
     * @var array<int, string>
     */
    protected $visible = [
        'id',
        'name',
        'device_id',
        'device_type',
        'latest_ip_address',
        'last_used_at',
        'expires_at',
        'is_current',
        'is_expired',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'is_current',
        'is_expired',
    ];

    /**
     * Scope to get only active (non-expired) tokens.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * Scope to get only expired tokens.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope to get tokens for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('tokenable_type', User::class)
                     ->where('tokenable_id', $userId);
    }

    /**
     * Scope to filter by device type.
     */
    public function scopeOfDeviceType(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', strtolower($deviceType));
    }

    /**
     * Check if this token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if this token is active (not expired).
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get the is_expired attribute.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    /**
     * Get whether this is the current request's token.
     */
    public function getIsCurrentAttribute(): bool
    {
        $currentToken = request()->user()?->currentAccessToken();
        return $currentToken && $currentToken->id === $this->id;
    }

    /**
     * Force device type to be lower-case.
     */
    public function setDeviceTypeAttribute(?string $value): void
    {
        $this->attributes['device_type'] = $value ? strtolower($value) : null;
    }

    /**
     * Find a token by device_id and device_type combination.
     *
     * @param string $deviceId
     * @param string $deviceType
     * @return static|null
     */
    public static function findByDevice(string $deviceId, string $deviceType): ?static
    {
        return static::where('device_id', $deviceId)
            ->where('device_type', strtolower($deviceType))
            ->first();
    }

    /**
     * Find an active token by device_id and device_type.
     *
     * @param string $deviceId
     * @param string $deviceType
     * @return static|null
     */
    public static function findActiveByDevice(string $deviceId, string $deviceType): ?static
    {
        return static::where('device_id', $deviceId)
            ->where('device_type', strtolower($deviceType))
            ->active()
            ->first();
    }

    /**
     * Delete any existing token for the given device.
     *
     * @param string $deviceId
     * @param string $deviceType
     * @return int Number of deleted tokens
     */
    public static function deleteByDevice(string $deviceId, string $deviceType): int
    {
        return static::where('device_id', $deviceId)
            ->where('device_type', strtolower($deviceType))
            ->delete();
    }

    /**
     * Update the push token for this device token.
     *
     * @param string|null $pushToken
     * @return bool
     */
    public function updatePushToken(?string $pushToken): bool
    {
        return $this->update(['device_push_token' => $pushToken]);
    }

    /**
     * Update the latest IP address for this device token.
     *
     * @param string|null $ipAddress
     * @return bool
     */
    public function updateIpAddress(?string $ipAddress): bool
    {
        return $this->update(['latest_ip_address' => $ipAddress]);
    }

    /**
     * Refresh the token - generates a new token string and extends expiry.
     * Returns the new plain text token.
     *
     * @param int|null $expiryDays Days until expiry (default: DEFAULT_EXPIRY_DAYS)
     * @return string The new plain text token
     */
    public function refreshToken(?int $expiryDays = null): string
    {
        $plainTextToken = Str::random(40);

        $this->update([
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => Carbon::now()->addDays($expiryDays ?? self::DEFAULT_EXPIRY_DAYS),
        ]);

        return $this->getKey() . '|' . $plainTextToken;
    }

    /**
     * Extend the token expiry without changing the token itself.
     *
     * @param int|null $expiryDays Days until expiry (default: DEFAULT_EXPIRY_DAYS)
     * @return bool
     */
    public function extendExpiry(?int $expiryDays = null): bool
    {
        return $this->update([
            'expires_at' => Carbon::now()->addDays($expiryDays ?? self::DEFAULT_EXPIRY_DAYS),
        ]);
    }

    /**
     * Revoke (delete) this token.
     *
     * @return bool|null
     */
    public function revoke(): ?bool
    {
        return $this->delete();
    }

    /**
     * Get a displayable summary of this device/token for API responses.
     *
     * @return array
     */
    public function toDeviceArray(): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'device_type' => $this->device_type,
            'name' => $this->name,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'latest_ip_address' => $this->latest_ip_address,
            'is_current' => $this->is_current,
            'is_expired' => $this->is_expired,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Purge all expired tokens from the database.
     *
     * @return int Number of deleted tokens
     */
    public static function purgeExpired(): int
    {
        return static::expired()->delete();
    }
}
