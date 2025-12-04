<?php

namespace EasyPack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'data',
        'topic',
        'tokens',
        'status',
        'scheduled_at',
        'sent_at',
        'category',
        'priority',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'tokens',
    ];

    /**
     * The attributes that should be visible for serialization.
     * Used for API responses and Swagger documentation.
     *
     * @var list<string>
     */
    protected $visible = [
        'id',
        'title',
        'message',
        'data',
        'topic',
        'status',
        'category',
        'priority',
        'scheduled_at',
        'sent_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'tokens' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    /**
     * Searchable fields for the model.
     */
    public array $searchable = [
        'title',
        'message',
    ];

    /**
     * Scope for pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for notifications ready to be sent.
     */
    public function scopeReadyToSend($query)
    {
        return $query->pending()
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            });
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }
}
