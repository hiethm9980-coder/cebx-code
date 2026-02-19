<?php

namespace App\Models;

use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PricingBreakdown — FR-BRP-001/006
 * Immutable audit record of how a price was calculated.
 */
class PricingBreakdown extends Model
{
    use HasFactory, HasUuids, BelongsToAccount;

    protected $fillable = [
        'account_id', 'entity_type', 'entity_id', 'correlation_id',
        'carrier_code', 'service_code', 'origin_country', 'destination_country',
        'weight', 'zone', 'shipment_type',
        'net_rate', 'markup_amount', 'service_fee', 'surcharge', 'discount',
        'tax_amount', 'pre_rounding_total', 'retail_rate',
        'rule_set_id', 'rule_set_version', 'applied_rules', 'guardrail_adjustments',
        'rounding_policy', 'currency', 'plan_slug', 'expired_plan_surcharge',
    ];

    protected $casts = [
        'net_rate'                => 'decimal:2',
        'markup_amount'           => 'decimal:2',
        'service_fee'             => 'decimal:2',
        'surcharge'               => 'decimal:2',
        'discount'                => 'decimal:2',
        'tax_amount'              => 'decimal:2',
        'pre_rounding_total'      => 'decimal:2',
        'retail_rate'             => 'decimal:2',
        'weight'                  => 'decimal:2',
        'applied_rules'           => 'array',
        'guardrail_adjustments'   => 'array',
        'expired_plan_surcharge'  => 'boolean',
    ];

    public function getProfitAttribute(): float
    {
        return round((float) $this->retail_rate - (float) $this->net_rate, 2);
    }

    public function getMarginPercentAttribute(): float
    {
        if ((float) $this->retail_rate <= 0) return 0;
        return round(($this->profit / (float) $this->retail_rate) * 100, 2);
    }

    public function scopeForEntity($query, string $type, string $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }
}
