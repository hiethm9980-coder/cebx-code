<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WalletTransaction extends Model {
    protected $guarded = [];
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
}
