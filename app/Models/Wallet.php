<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
class Wallet extends Model {
    protected $guarded = [];
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function transactions(): HasMany { return $this->hasMany(WalletTransaction::class)->latest(); }
}
