<?php

namespace EasyPack\Models;

use EasyPack\Traits\HasExtraApiFields;
use EasyPack\Traits\HasMediaAttachments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia, HasMediaAttachments, HasExtraApiFields;

    /**
     * The guard name for Spatie Permission.
     *
     * @var string
     */
    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be visible for serialization.
     * Used for API responses and Swagger documentation.
     *
     * @var list<string>
     */
    protected $visible = [
        'id',
        'name',
        'email',
        'avatar_url',
        'avatar_thumb_url',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
        'avatar_thumb_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Register media collections for the user.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('documents');
    }

    /**
     * Register media conversions for image processing.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('avatar');

        $this->addMediaConversion('preview')
            ->width(400)
            ->height(400)
            ->performOnCollections('avatar', 'documents');
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('avatar');
        return $media?->getUrl();
    }

    /**
     * Get the user's avatar thumbnail URL.
     */
    public function getAvatarThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('avatar');
        return $media?->getUrl('thumb');
    }

    /**
     * Fields that may be added to the API responses.
     * These fields are not stored in the database but can be dynamically set.
     *
     * @return array<string>
     */
    public function getExtraApiFields(): array
    {
        return [
            'access_token',
        ];
    }

    /**
     * Create a new personal access token for a device.
     * Ensures only one token exists per device (device_id + device_type).
     *
     * @param string $name Token name
     * @param string $deviceId Unique device identifier
     * @param string $deviceType Device type (e.g., 'APPLE', 'ANDROID')
     * @param string|null $devicePushToken Push notification token (optional)
     * @param string|null $ipAddress Current IP address (optional)
     * @param array $abilities Token abilities (default: ['*'])
     * @param int|null $expiryDays Days until token expires (default: 90)
     * @return NewAccessToken
     */
    public function createDeviceToken(
        string $name,
        string $deviceId,
        string $deviceType,
        ?string $devicePushToken = null,
        ?string $ipAddress = null,
        array $abilities = ['*'],
        ?int $expiryDays = null
    ): NewAccessToken {
        // Delete any existing token for this device
        PersonalAccessToken::deleteByDevice($deviceId, $deviceType);

        // Create the new token with expiry
        $expiresAt = Carbon::now()->addDays($expiryDays ?? PersonalAccessToken::DEFAULT_EXPIRY_DAYS);
        $token = $this->createToken($name, $abilities, $expiresAt);

        // Update the token record with device information
        $token->accessToken->update([
            'device_id' => $deviceId,
            'device_type' => strtolower($deviceType),
            'device_push_token' => $devicePushToken,
            'latest_ip_address' => $ipAddress,
        ]);

        return $token;
    }

    /**
     * Get all active devices/tokens for this user.
     *
     * @return Collection<PersonalAccessToken>
     */
    public function getDevices(): Collection
    {
        return $this->tokens()
            ->active()
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Get all devices including expired ones.
     *
     * @return Collection<PersonalAccessToken>
     */
    public function getAllDevices(): Collection
    {
        return $this->tokens()
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Get a specific device by device_id.
     *
     * @param string $deviceId
     * @return PersonalAccessToken|null
     */
    public function getDevice(string $deviceId): ?PersonalAccessToken
    {
        return $this->tokens()
            ->where('device_id', $deviceId)
            ->first();
    }

    /**
     * Logout from a specific device by device_id.
     *
     * @param string $deviceId
     * @return bool True if device was found and deleted
     */
    public function logoutDevice(string $deviceId): bool
    {
        $deleted = $this->tokens()
            ->where('device_id', $deviceId)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Logout from all devices (revoke all tokens).
     *
     * @return int Number of tokens revoked
     */
    public function logoutAllDevices(): int
    {
        return $this->tokens()->delete();
    }

    /**
     * Logout from all devices except the current one.
     *
     * @return int Number of tokens revoked
     */
    public function logoutOtherDevices(): int
    {
        $currentToken = $this->currentAccessToken();

        if (!$currentToken) {
            return $this->logoutAllDevices();
        }

        return $this->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();
    }

    /**
     * Update push token for a specific device.
     *
     * @param string $deviceId
     * @param string|null $pushToken
     * @return bool
     */
    public function updateDevicePushToken(string $deviceId, ?string $pushToken): bool
    {
        $device = $this->getDevice($deviceId);

        if (!$device) {
            return false;
        }

        return $device->updatePushToken($pushToken);
    }

    /**
     * Get the count of active devices.
     *
     * @return int
     */
    public function getActiveDeviceCount(): int
    {
        return $this->tokens()->active()->count();
    }

    /**
     * Check if user has a specific device.
     *
     * @param string $deviceId
     * @return bool
     */
    public function hasDevice(string $deviceId): bool
    {
        return $this->tokens()
            ->where('device_id', $deviceId)
            ->exists();
    }

    /**
     * Get devices by type (e.g., 'apple', 'android').
     *
     * @param string $deviceType
     * @return Collection<PersonalAccessToken>
     */
    public function getDevicesByType(string $deviceType): Collection
    {
        return $this->tokens()
            ->ofDeviceType($deviceType)
            ->active()
            ->get();
    }

    /**
     * Get notification preferences for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function notificationPreferences(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Get or create notification preferences for this user.
     *
     * @return NotificationPreference
     */
    public function getNotificationPreferences(): NotificationPreference
    {
        return NotificationPreference::forUser($this);
    }
}
