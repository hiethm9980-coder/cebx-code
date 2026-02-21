<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    // لا نستخدم HasUuids لأن جدول users على السيرفر قد يكون من الـ migration الأولى (id = bigint)
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['last_login_at' => 'datetime', 'is_active' => 'boolean', 'is_super_admin' => 'boolean'];

    public function account(): BelongsTo  { return $this->belongsTo(Account::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function shipments(): HasMany  { return $this->hasMany(Shipment::class); }
    public function tickets(): HasMany    { return $this->hasMany(SupportTicket::class); }
}
