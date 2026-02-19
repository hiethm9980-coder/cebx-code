<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

class Branch extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'company_id', 'name', 'code', 'country', 'city', 'state',
        'postal_code', 'address', 'branch_type', 'phone', 'email',
        'manager_name', 'manager_user_id', 'latitude', 'longitude',
        'status', 'operating_hours', 'capabilities', 'metadata',
    ];

    protected $casts = [
        'operating_hours' => 'json',
        'capabilities' => 'json',
        'metadata' => 'json',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function staff() { return $this->hasMany(BranchStaff::class); }
    public function users() { return $this->belongsToMany(User::class, 'branch_staff')->withPivot('role', 'is_primary'); }
    public function manager() { return $this->belongsTo(User::class, 'manager_user_id'); }
    public function drivers() { return $this->hasMany(Driver::class); }
    public function originShipments() { return $this->hasMany(Shipment::class, 'origin_branch_id'); }
    public function destinationShipments() { return $this->hasMany(Shipment::class, 'destination_branch_id'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeOfType($q, $type) { return $q->where('branch_type', $type); }
    public function scopeInCountry($q, $c) { return $q->where('country', $c); }
}
