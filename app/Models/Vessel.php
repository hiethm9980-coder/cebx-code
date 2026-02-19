<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

class Vessel extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'vessel_name', 'imo_number', 'mmsi', 'call_sign',
        'flag', 'vessel_type', 'operator', 'capacity_teu', 'max_deadweight',
        'status', 'metadata',
    ];

    protected $casts = [
        'capacity_teu' => 'integer',
        'max_deadweight' => 'decimal:2',
        'metadata' => 'json',
    ];

    public function schedules() { return $this->hasMany(VesselSchedule::class); }
    public function activeSchedules() { return $this->schedules()->whereIn('status', ['scheduled', 'departed', 'in_transit']); }
}
