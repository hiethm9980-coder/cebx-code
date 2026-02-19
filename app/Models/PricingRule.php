<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PricingRule — FR-RT-002/003/008: Configurable markup & pricing conditions.
 */
class PricingRule extends Model
{
    use HasUuids, HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_id', 'name', 'description',
        'carrier_code', 'service_code', 'origin_country', 'destination_country',
        'destination_zone', 'shipment_type', 'min_weight', 'max_weight',
        'store_id', 'is_cod',
        'markup_type', 'markup_percentage', 'markup_fixed',
        'min_profit', 'min_retail_price', 'max_retail_price',
        'service_fee_fixed', 'service_fee_percentage',
        'rounding_mode', 'rounding_precision',
        'is_expired_surcharge', 'expired_surcharge_percentage',
        'priority', 'is_active', 'is_default', 'currency',
    ];

    protected $casts = [
        'markup_percentage'           => 'decimal:4',
        'markup_fixed'                => 'decimal:2',
        'min_profit'                  => 'decimal:2',
        'min_retail_price'            => 'decimal:2',
        'max_retail_price'            => 'decimal:2',
        'service_fee_fixed'           => 'decimal:2',
        'service_fee_percentage'      => 'decimal:4',
        'rounding_precision'          => 'decimal:2',
        'expired_surcharge_percentage'=> 'decimal:4',
        'min_weight'                  => 'decimal:3',
        'max_weight'                  => 'decimal:3',
        'priority'                    => 'integer',
        'is_active'                   => 'boolean',
        'is_default'                  => 'boolean',
        'is_expired_surcharge'        => 'boolean',
        'is_cod'                      => 'boolean',
    ];

    /**
     * Check if this rule matches the given shipment context.
     */
    public function matches(array $context): bool
    {
        if ($this->carrier_code && ($context['carrier_code'] ?? null) !== $this->carrier_code) return false;
        if ($this->service_code && ($context['service_code'] ?? null) !== $this->service_code) return false;
        if ($this->origin_country && ($context['origin_country'] ?? null) !== $this->origin_country) return false;
        if ($this->destination_country && ($context['destination_country'] ?? null) !== $this->destination_country) return false;
        if ($this->store_id && ($context['store_id'] ?? null) !== $this->store_id) return false;

        if ($this->shipment_type !== 'any') {
            $isIntl = ($context['origin_country'] ?? 'SA') !== ($context['destination_country'] ?? 'SA');
            if ($this->shipment_type === 'domestic' && $isIntl) return false;
            if ($this->shipment_type === 'international' && !$isIntl) return false;
        }

        $weight = $context['chargeable_weight'] ?? $context['total_weight'] ?? 0;
        if ($this->min_weight && $weight < (float) $this->min_weight) return false;
        if ($this->max_weight && $weight > (float) $this->max_weight) return false;

        if ($this->is_cod !== null && ($context['is_cod'] ?? false) !== $this->is_cod) return false;

        return true;
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeForAccount($q, $accountId)
    {
        return $q->where(function ($q) use ($accountId) {
            $q->where('account_id', $accountId)->orWhereNull('account_id');
        });
    }
    public function scopeByPriority($q) { return $q->orderBy('priority')->orderByDesc('account_id'); }
    public function scopeDefaults($q)   { return $q->where('is_default', true); }
}
