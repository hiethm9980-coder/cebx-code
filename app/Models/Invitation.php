<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToAccount;

class Invitation extends Model
{
    use HasFactory, HasUuids, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id',
        'email',
        'name',
        'role_id',
        'token',
        'status',
        'invited_by',
        'accepted_by',
        'expires_at',
        'accepted_at',
        'cancelled_at',
        'last_sent_at',
        'send_count',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'accepted_at'  => 'datetime',
        'cancelled_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'send_count'   => 'integer',
    ];

    // ─── Status Constants ────────────────────────────────────────

    const STATUS_PENDING   = 'pending';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // ─── Relationships ───────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    // ─── Status Helpers ──────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->isPending() && $this->expires_at->isPast());
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if this invitation can still be accepted.
     */
    public function isUsable(): bool
    {
        return $this->isPending() && !$this->expires_at->isPast();
    }

    /**
     * Check if this invitation can be resent.
     * Only pending invitations can be resent.
     */
    public function canResend(): bool
    {
        return $this->isPending();
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeExpiredButPending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where('expires_at', '<', now());
    }

    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', strtolower($email));
    }
}
