<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Claim extends Model {
    protected $guarded = [];
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
}
