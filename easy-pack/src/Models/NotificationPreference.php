<?php

namespace EasyPack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'push_enabled',
        'email_enabled',
        'sms_enabled',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'categories',
    ];

    /**
     * The attributes that should be visible for serialization.
     * Used for API responses and Swagger documentation.
     *
     * @var list<string>
     */
    protected $visible = [
        'id',
        'user_id',
        'push_enabled',
        'email_enabled',
        'sms_enabled',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'categories',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'categories' => 'array',
    ];

    /**
     * Get the user that owns the notification preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create notification preferences for a user.
     */
    public static function forUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => false,
                'quiet_hours_enabled' => false,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
                'categories' => [],
            ]
        );
    }

    /**
     * Check if a category is enabled.
     */
    public function isCategoryEnabled(string $category): bool
    {
        $categories = $this->categories ?? [];

        // If no categories are set, all are enabled by default
        if (empty($categories)) {
            return true;
        }

        return in_array($category, $categories);
    }

    /**
     * Enable a category.
     */
    public function enableCategory(string $category): bool
    {
        $categories = $this->categories ?? [];

        if (!in_array($category, $categories)) {
            $categories[] = $category;
            return $this->update(['categories' => $categories]);
        }

        return true;
    }

    /**
     * Disable a category.
     */
    public function disableCategory(string $category): bool
    {
        $categories = $this->categories ?? [];
        $categories = array_filter($categories, fn($c) => $c !== $category);

        return $this->update(['categories' => array_values($categories)]);
    }

    /**
     * Check if currently in quiet hours.
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled) {
            return false;
        }

        $now = now();
        $start = now()->setTimeFromTimeString($this->quiet_hours_start);
        $end = now()->setTimeFromTimeString($this->quiet_hours_end);

        // Handle overnight quiet hours (e.g., 22:00 - 08:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }
}
