<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

class CustomsDeclaration extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'shipment_id', 'broker_id', 'branch_id',
        'declaration_number', 'declaration_type', 'customs_office',
        'origin_country', 'destination_country', 'incoterm_code', 'customs_status',
        'declared_value', 'declared_currency',
        'duty_amount', 'vat_amount', 'excise_amount', 'other_fees',
        'total_customs_charges', 'broker_fee',
        'inspection_flag', 'inspection_date', 'inspection_result',
        'inspection_notes', 'inspector_user_id',
        'submitted_at', 'cleared_at', 'duty_paid_at', 'duty_payment_ref',
        'notes', 'metadata',
    ];

    protected $casts = [
        'declared_value' => 'decimal:2', 'duty_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2', 'excise_amount' => 'decimal:2',
        'other_fees' => 'decimal:2', 'total_customs_charges' => 'decimal:2',
        'broker_fee' => 'decimal:2',
        'inspection_flag' => 'boolean',
        'inspection_date' => 'datetime', 'submitted_at' => 'datetime',
        'cleared_at' => 'datetime', 'duty_paid_at' => 'datetime',
        'metadata' => 'json',
    ];

    // Status machine transitions
    const STATUS_TRANSITIONS = [
        'draft' => ['documents_pending', 'cancelled'],
        'documents_pending' => ['submitted', 'draft', 'cancelled'],
        'submitted' => ['under_review', 'cancelled'],
        'under_review' => ['inspection_required', 'duty_assessment', 'held', 'rejected'],
        'inspection_required' => ['inspecting'],
        'inspecting' => ['duty_assessment', 'held', 'rejected'],
        'duty_assessment' => ['payment_pending'],
        'payment_pending' => ['duty_paid'],
        'duty_paid' => ['cleared'],
        'held' => ['under_review', 'rejected', 'cancelled'],
        'cleared' => [],
        'rejected' => ['draft'],
        'cancelled' => [],
    ];

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::STATUS_TRANSITIONS[$this->customs_status] ?? []);
    }

    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function broker() { return $this->belongsTo(CustomsBroker::class, 'broker_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function documents() { return $this->hasMany(CustomsDocument::class, 'declaration_id'); }
    public function items() { return $this->hasMany(ShipmentItem::class, 'declaration_id'); }
    public function inspector() { return $this->belongsTo(User::class, 'inspector_user_id'); }

    public function calculateTotalCharges(): float
    {
        $this->total_customs_charges = $this->duty_amount + $this->vat_amount + $this->excise_amount + $this->other_fees + $this->broker_fee;
        return $this->total_customs_charges;
    }
}
