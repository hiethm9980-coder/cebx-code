<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
class Store extends Model {
    protected $guarded = [];
    protected $casts = ['last_sync_at' => 'datetime'];
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
}
