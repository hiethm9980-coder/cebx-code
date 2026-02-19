<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToAccount;

class Company extends Model
{
    use HasUuids, HasFactory, SoftDeletes, BelongsToAccount;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'name', 'legal_name', 'registration_number', 'tax_id',
        'country', 'base_currency', 'timezone', 'industry', 'status',
        'logo_url', 'website', 'phone', 'email', 'address',
        'settings', 'metadata',
    ];

    protected $casts = [
        'settings' => 'json',
        'metadata' => 'json',
    ];

    public function account() { return $this->belongsTo(Account::class); }
    public function branches() { return $this->hasMany(Branch::class); }
    public function shipments() { return $this->hasMany(Shipment::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
}
