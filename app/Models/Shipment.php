<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
class Shipment extends Model {
    protected $guarded = [];
    protected $casts = ['shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'is_cod' => 'boolean'];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function events(): HasMany { return $this->hasMany(ShipmentEvent::class)->orderByDesc('event_at'); }
    public function claims(): HasMany { return $this->hasMany(Claim::class); }

    public static function generateRef(): string {
        return 'SHP-' . date('Y') . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT);
    }
}
