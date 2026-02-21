<?php
// ╔═══════════════════════════════════════════╗
// ║  app/Models/Account.php                   ║
// ╚═══════════════════════════════════════════╝
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function users(): HasMany     { return $this->hasMany(User::class); }
    public function shipments(): HasMany { return $this->hasMany(Shipment::class); }
    public function orders(): HasMany    { return $this->hasMany(Order::class); }
    public function stores(): HasMany    { return $this->hasMany(Store::class); }
    public function wallet(): HasOne     { return $this->hasOne(Wallet::class); }
    public function addresses(): HasMany { return $this->hasMany(Address::class); }
    public function tickets(): HasMany   { return $this->hasMany(SupportTicket::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function invitations(): HasMany   { return $this->hasMany(Invitation::class); }
    public function claims(): HasMany    { return $this->hasMany(Claim::class); }
    public function kycRequests(): HasMany { return $this->hasMany(KycRequest::class); }
}
