<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RiskAlert extends Model {
    protected $guarded = [];
    public function rule(): BelongsTo { return $this->belongsTo(RiskRule::class, 'risk_rule_id'); }
}
