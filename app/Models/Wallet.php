<?php

namespace App\Models;

use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, HasUuids, BelongsToAccount;

    protected $guarded = [];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
        'low_balance_threshold' => 'decimal:2',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_CLOSED = 'closed';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class)->latest('created_at');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isBelowThreshold(): bool
    {
        if ($this->low_balance_threshold === null) {
            return false;
        }

        return (float) $this->available_balance < (float) $this->low_balance_threshold;
    }

    /**
     * Return a wallet summary array.
     *
     * When $canViewBalance is true the full balance fields are included.
     * When false, sensitive balance fields are OMITTED entirely and a boolean
     * has_sufficient_balance indicator is included instead (FR-IAM-017 masking).
     *
     * @return array<string, mixed>
     */
    public function summary(bool $canViewBalance): array
    {
        $base = [
            'id'         => $this->id,
            'account_id' => $this->account_id,
            'currency'   => $this->currency,
            'status'     => $this->status,
        ];

        if ($canViewBalance) {
            return array_merge($base, [
                'available_balance'    => number_format((float) $this->available_balance, 2, '.', ''),
                'locked_balance'       => number_format((float) $this->locked_balance, 2, '.', ''),
                'low_balance_threshold' => $this->low_balance_threshold !== null
                    ? number_format((float) $this->low_balance_threshold, 2, '.', '')
                    : null,
            ]);
        }

        // Masked: reveal only whether the balance is positive (no exact amount)
        return array_merge($base, [
            'has_sufficient_balance' => (float) $this->available_balance > 0,
        ]);
    }
}
