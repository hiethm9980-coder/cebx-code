<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToAccount;

/**
 * Wallet — One per account.
 *
 * FR-IAM-017/019: RBAC-gated wallet operations
 * FR-BW-001: Auto-created wallet with currency and balance
 */
class Wallet extends Model
{
    use HasUuids, HasFactory, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'currency', 'available_balance', 'locked_balance',
        'low_balance_threshold', 'status',
    ];

    protected $casts = [
        'available_balance'     => 'decimal:2',
        'locked_balance'        => 'decimal:2',
        'low_balance_threshold' => 'decimal:2',
    ];

    // ─── Status ──────────────────────────────────────────────────

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_CLOSED = 'closed';

    // ─── Relationships ───────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class)->orderByDesc('created_at');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isFrozen(): bool
    {
        return $this->status === self::STATUS_FROZEN;
    }

    public function totalBalance(): float
    {
        return (float) $this->available_balance + (float) $this->locked_balance;
    }

    public function isBelowThreshold(): bool
    {
        if ($this->low_balance_threshold === null) {
            return false;
        }
        return (float) $this->available_balance < (float) $this->low_balance_threshold;
    }

    /**
     * Summary for API response (respects permissions).
     */
    public function summary(bool $includeDetails = false): array
    {
        $data = [
            'id'       => $this->id,
            'currency' => $this->currency,
            'status'   => $this->status,
        ];

        if ($includeDetails) {
            $data['available_balance']     = $this->available_balance;
            $data['locked_balance']        = $this->locked_balance;
            $data['total_balance']         = $this->totalBalance();
            $data['low_balance_threshold'] = $this->low_balance_threshold;
            $data['is_below_threshold']    = $this->isBelowThreshold();
        } else {
            // Masked summary for users without wallet:balance permission
            $data['has_sufficient_balance'] = (float) $this->available_balance > 0;
        }

        return $data;
    }
}
