<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Order extends Model {
    protected $guarded = [];
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
}
