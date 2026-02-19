<?php

namespace App\Models;

use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification — FR-NTF-001/003/005/008
 *
 * Complete notification record with delivery tracking, retry, and rate limiting.
 */
class Notification extends Model
{
    use HasFactory, HasUuids, BelongsToAccount;

    protected $fillable = [
        'account_id', 'user_id', 'event_type', 'entity_type', 'entity_id',
        'event_data', 'channel', 'destination', 'language',
        'subject', 'body', 'template_id',
        'status', 'retry_count', 'max_retries', 'next_retry_at', 'failure_reason', 'external_id',
        'is_batched', 'batch_id', 'is_throttled',
        'scheduled_at', 'sent_at', 'delivered_at', 'read_at',
        'provider', 'provider_response',
    ];

    protected $casts = [
        'event_data'        => 'array',
        'provider_response' => 'array',
        'is_batched'        => 'boolean',
        'is_throttled'      => 'boolean',
        'next_retry_at'     => 'datetime',
        'scheduled_at'      => 'datetime',
        'sent_at'           => 'datetime',
        'delivered_at'      => 'datetime',
        'read_at'           => 'datetime',
    ];

    // ── Status Constants ─────────────────────────────────────
    const STATUS_PENDING    = 'pending';
    const STATUS_QUEUED     = 'queued';
    const STATUS_SENDING    = 'sending';
    const STATUS_SENT       = 'sent';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_FAILED     = 'failed';
    const STATUS_BOUNCED    = 'bounced';
    const STATUS_RETRYING   = 'retrying';
    const STATUS_DLQ        = 'dlq';

    // ── Event Type Constants ─────────────────────────────────
    const EVENT_ORDER_CREATED         = 'order.created';
    const EVENT_SHIPMENT_CREATED      = 'shipment.created';
    const EVENT_LABEL_CREATED         = 'shipment.label_created';
    const EVENT_SHIPMENT_PICKED_UP    = 'shipment.picked_up';
    const EVENT_SHIPMENT_IN_TRANSIT   = 'shipment.in_transit';
    const EVENT_SHIPMENT_OUT_DELIVERY = 'shipment.out_for_delivery';
    const EVENT_SHIPMENT_DELIVERED    = 'shipment.delivered';
    const EVENT_SHIPMENT_EXCEPTION    = 'shipment.exception';
    const EVENT_SHIPMENT_RETURNED     = 'shipment.returned';
    const EVENT_PAYMENT_FAILED        = 'payment.failed';
    const EVENT_PAYMENT_SUCCESS       = 'payment.success';
    const EVENT_KYC_SUBMITTED         = 'kyc.submitted';
    const EVENT_KYC_VERIFIED          = 'kyc.verified';
    const EVENT_KYC_REJECTED          = 'kyc.rejected';
    const EVENT_ACCOUNT_INVITE        = 'account.invite';
    const EVENT_SECURITY_LOGIN        = 'security.login';
    const EVENT_SECURITY_2FA          = 'security.2fa';

    const CORE_EVENTS = [
        self::EVENT_LABEL_CREATED,
        self::EVENT_SHIPMENT_IN_TRANSIT,
        self::EVENT_SHIPMENT_OUT_DELIVERY,
        self::EVENT_SHIPMENT_DELIVERED,
        self::EVENT_SHIPMENT_EXCEPTION,
    ];

    // ── Channel Constants ────────────────────────────────────
    const CHANNEL_EMAIL   = 'email';
    const CHANNEL_SMS     = 'sms';
    const CHANNEL_IN_APP  = 'in_app';
    const CHANNEL_WEBHOOK = 'webhook';
    const CHANNEL_SLACK   = 'slack';

    // ── Relationships ────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Status Methods ───────────────────────────────────────

    public function markSent(?string $externalId = null): void
    {
        $this->update([
            'status'      => self::STATUS_SENT,
            'sent_at'     => now(),
            'external_id' => $externalId,
        ]);
    }

    public function markDelivered(): void
    {
        $this->update(['status' => self::STATUS_DELIVERED, 'delivered_at' => now()]);
    }

    public function markRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markFailed(string $reason): void
    {
        if ($this->retry_count < $this->max_retries) {
            $backoff = pow(2, $this->retry_count) * 60; // exponential backoff
            $this->update([
                'status'        => self::STATUS_RETRYING,
                'failure_reason' => $reason,
                'retry_count'   => $this->retry_count + 1,
                'next_retry_at' => now()->addSeconds($backoff),
            ]);
        } else {
            $this->update([
                'status'        => self::STATUS_DLQ,
                'failure_reason' => $reason,
            ]);
        }
    }

    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries
            && in_array($this->status, [self::STATUS_FAILED, self::STATUS_RETRYING]);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_RETRYING)
            ->where('next_retry_at', '<=', now());
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId)->orderBy('created_at', 'desc');
    }

    public function scopeUnread($query)
    {
        return $query->where('channel', self::CHANNEL_IN_APP)->whereNull('read_at');
    }

    public function scopeForEntity($query, string $type, string $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }
}
