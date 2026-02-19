<?php

namespace App\Models;

use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SupportTicket — FR-ADM-008
 */
class SupportTicket extends Model
{
    use HasFactory, HasUuids, BelongsToAccount;

    protected $fillable = [
        'account_id', 'user_id', 'ticket_number', 'subject', 'description',
        'category', 'priority', 'status', 'entity_type', 'entity_id',
        'assigned_to', 'assigned_team',
        'first_response_at', 'resolved_at', 'closed_at', 'resolution_notes',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at'       => 'datetime',
        'closed_at'         => 'datetime',
    ];

    const STATUS_OPEN             = 'open';
    const STATUS_IN_PROGRESS      = 'in_progress';
    const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    const STATUS_WAITING_AGENT    = 'waiting_agent';
    const STATUS_RESOLVED         = 'resolved';
    const STATUS_CLOSED           = 'closed';

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function replies(): HasMany { return $this->hasMany(SupportTicketReply::class, 'ticket_id'); }

    public static function generateNumber(): string
    {
        return 'TKT-' . now()->format('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function assign(string $userId, ?string $team = null): void
    {
        $this->update(['assigned_to' => $userId, 'assigned_team' => $team, 'status' => self::STATUS_IN_PROGRESS]);
    }

    public function resolve(string $notes): void
    {
        $this->update(['status' => self::STATUS_RESOLVED, 'resolved_at' => now(), 'resolution_notes' => $notes]);
    }

    public function close(): void
    {
        $this->update(['status' => self::STATUS_CLOSED, 'closed_at' => now()]);
    }

    public function scopeOpen($query) { return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_AGENT]); }
    public function scopeByPriority($query, string $priority) { return $query->where('priority', $priority); }
}
