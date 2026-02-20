<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Schedule extends Model {
    protected $guarded = [];
    protected $casts = ['departure_date' => 'datetime', 'arrival_date' => 'datetime'];
    public function vessel(): BelongsTo { return $this->belongsTo(Vessel::class); }
}
