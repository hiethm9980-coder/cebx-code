<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

class Claim extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'shipment_id', 'claim_number', 'claim_type', 'status',
        'description', 'claimed_amount', 'claimed_currency',
        'approved_amount', 'settled_amount', 'settlement_currency', 'settlement_ref',
        'incident_date', 'incident_location', 'filed_by', 'assigned_to', 'approved_by',
        'resolution_notes', 'rejection_reason',
        'submitted_at', 'resolved_at', 'settled_at', 'sla_deadline', 'metadata',
    ];

    protected $casts = [
        'claimed_amount' => 'decimal:2', 'approved_amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'incident_date' => 'date', 'sla_deadline' => 'date',
        'submitted_at' => 'datetime', 'resolved_at' => 'datetime',
        'settled_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function filer() { return $this->belongsTo(User::class, 'filed_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function documents() { return $this->hasMany(ClaimDocument::class); }
    public function history() { return $this->hasMany(ClaimHistory::class)->orderByDesc('created_at'); }

    public static function generateNumber(): string { return 'CLM-' . strtoupper(substr(uniqid(), -8)); }
}
