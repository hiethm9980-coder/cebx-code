<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

class Driver extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'branch_id', 'name', 'phone', 'email',
        'license_number', 'license_expiry', 'vehicle_type', 'vehicle_plate',
        'id_number', 'nationality', 'latitude', 'longitude', 'location_updated_at',
        'status', 'rating', 'total_deliveries', 'successful_deliveries',
        'photo_url', 'zones', 'metadata',
    ];

    protected $casts = [
        'license_expiry' => 'date', 'location_updated_at' => 'datetime',
        'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
        'rating' => 'decimal:2',
        'zones' => 'json', 'metadata' => 'json',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function assignments() { return $this->hasMany(DeliveryAssignment::class); }
    public function activeAssignments() { return $this->assignments()->whereNotIn('status', ['delivered', 'failed', 'returned', 'cancelled']); }

    public function scopeAvailable($q) { return $q->where('status', 'available'); }
    public function scopeInZone($q, $city) { return $q->whereJsonContains('zones', $city); }

    public function getSuccessRate(): float
    {
        return $this->total_deliveries > 0 ? round(($this->successful_deliveries / $this->total_deliveries) * 100, 1) : 100;
    }

    public function updateLocation(float $lat, float $lng): void
    {
        $this->update(['latitude' => $lat, 'longitude' => $lng, 'location_updated_at' => now()]);
    }
}
