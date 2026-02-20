<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Container extends Model {
    protected $guarded = [];
    public function vessel(): BelongsTo { return $this->belongsTo(Vessel::class); }
}
