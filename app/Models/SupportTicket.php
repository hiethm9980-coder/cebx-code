<?php

namespace App\Models;

use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory, HasUuids, BelongsToAccount;

    public $incrementing = false;
    protected $keyType   = 'string';
    protected $guarded   = [];

    // ── Status Constants ───────────────────────────────────────
    public const STATUS_OPEN             = 'open';
    public const STATUS_IN_PROGRESS      = 'in_progress';
    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    public const STATUS_WAITING_AGENT    = 'waiting_agent';
    public const STATUS_RESOLVED         = 'resolved';
    public const STATUS_CLOSED           = 'closed';

    // ── Relations ─────────────────────────────────────────────
    public function account(): BelongsTo  { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }

    /** Replies sorted chronologically — uses the actual SupportTicketReply model. */
    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id')->oldest();
    }

    // ── Lifecycle Methods ─────────────────────────────────────

    public function assign(string $userId, ?string $team = null): void
    {
        $this->update([
            'assigned_to'   => $userId,
            'assigned_team' => $team,
            'status'        => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function resolve(string $notes): void
    {
        $this->update([
            'status'           => self::STATUS_RESOLVED,
            'resolution_notes' => $notes,
            'resolved_at'      => now(),
        ]);
    }

    public function close(?string $notes = null): void
    {
        $this->update([
            'status'           => self::STATUS_CLOSED,
            'resolution_notes' => $notes ?? $this->resolution_notes,
            'closed_at'        => now(),
        ]);
    }

    // ── Static Helpers ────────────────────────────────────────

    public static function generateNumber(): string
    {
        return 'TKT-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
