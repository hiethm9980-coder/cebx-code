<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

class Container extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'vessel_schedule_id', 'container_number', 'size', 'type',
        'seal_number', 'tare_weight', 'max_payload', 'current_weight',
        'temperature_min', 'temperature_max', 'location', 'status',
        'origin_branch_id', 'destination_branch_id', 'metadata',
    ];

    protected $casts = [
        'tare_weight' => 'decimal:2', 'max_payload' => 'decimal:2',
        'current_weight' => 'decimal:2',
        'temperature_min' => 'decimal:1', 'temperature_max' => 'decimal:1',
        'metadata' => 'json',
    ];

    public function vesselSchedule() { return $this->belongsTo(VesselSchedule::class); }
    public function shipments() { return $this->belongsToMany(Shipment::class, 'container_shipments')->withPivot('packages_count', 'weight', 'volume_cbm', 'loaded_at', 'unloaded_at'); }
    public function originBranch() { return $this->belongsTo(Branch::class, 'origin_branch_id'); }
    public function destinationBranch() { return $this->belongsTo(Branch::class, 'destination_branch_id'); }

    public function availablePayload(): float { return $this->max_payload - ($this->current_weight ?? 0); }
}
