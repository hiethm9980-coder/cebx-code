<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
class SupportTicket extends Model {
    protected $guarded = [];
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
    public function replies(): HasMany { return $this->hasMany(TicketReply::class)->oldest(); }
}
