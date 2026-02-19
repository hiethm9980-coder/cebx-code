<?php

namespace App\Services;

use App\Models\ExpiredPlanPolicy;
use App\Models\PricingBreakdown;
use App\Models\PricingRule;
use App\Models\PricingRuleSet;
use App\Models\RoundingPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * PricingEngineService — FR-BRP-001→008 (8 requirements)
 *
 * FR-BRP-001: Explainable, deterministic pricing with full audit trail
 * FR-BRP-002: Conditional pricing rules (carrier/destination/weight/service/store/type)
 * FR-BRP-003: Independent service fee (fixed or percentage)
 * FR-BRP-004: Guardrails — min price, min profit
 * FR-BRP-005: Currency rounding policy (up/down/nearest per currency)
 * FR-BRP-006: Store Pricing Breakdown in Quote/Shipment (snapshot)
 * FR-BRP-007: Alternative pricing on expired subscription
 * FR-BRP-008: Rule priority & conflict resolution (priority + cumulative flag)
 */
class PricingEngineService
{
    /**
     * FR-BRP-001/002/003/004/005/006/007/008: Full pricing calculation.
     *
     * @param  string  $accountId
     * @param  float   $netRate     Net cost from carrier
     * @param  array   $context     Shipment context {carrier_code, service_code, origin_country, destination_country, weight, zone, shipment_type, store_id, plan_slug, subscription_status}
     * @param  string  $entityType  'rate_quote' or 'shipment'
     * @param  string  $entityId
     * @return PricingBreakdown
     */
    public function calculatePrice(
        string $accountId,
        float  $netRate,
        array  $context,
        string $entityType = 'rate_quote',
        string $entityId = ''
    ): PricingBreakdown {
        $correlationId = 'PRC-' . Str::uuid()->toString();
        $currency = $context['currency'] ?? 'SAR';

        // ── 1. Resolve active rule set (FR-BRP-008) ──────────
        $ruleSet = PricingRuleSet::getActiveForAccount($accountId);
        $rules = $ruleSet ? $ruleSet->activeRules()->get() : collect();

        // If no BRP rule set, fall back to legacy PricingRule from RT module
        if ($rules->isEmpty()) {
            $rules = PricingRule::active()
                ->where(fn($q) => $q->where('account_id', $accountId)->orWhereNull('account_id'))
                ->orderBy('priority')
                ->get();
        }

        // ── 2. Match & prioritize rules (FR-BRP-002/008) ─────
        $matchedRules = $this->matchRules($rules, $context);

        // ── 3. Calculate components ──────────────────────────
        $markupAmount = 0;
        $serviceFee = 0;
        $surcharge = 0;
        $discount = 0;
        $appliedRules = [];
        $guardrailAdjustments = [];

        $minPrice = null;
        $minProfit = null;

        foreach ($matchedRules as $rule) {
            $effect = $this->getRuleEffect($rule, $netRate);

            $entry = [
                'rule_id' => $rule->id,
                'name'    => $rule->name ?? ($rule->type ?? 'legacy'),
                'type'    => $rule->type ?? $this->getLegacyType($rule),
                'value'   => (float) ($rule->value ?? 0),
                'effect'  => $effect,
            ];

            // Categorize effect
            if ($this->isMarkupRule($rule)) {
                $markupAmount += $effect;
            } elseif ($this->isServiceFeeRule($rule)) {
                $serviceFee += $effect;
            } elseif ($this->isDiscountRule($rule)) {
                $discount += abs($effect);
            } elseif ($this->isSurchargeRule($rule)) {
                $surcharge += $effect;
            } elseif ($this->isMinPriceRule($rule)) {
                $minPrice = (float) ($rule->value ?? $rule->min_retail_price ?? 0);
                $entry['effect'] = 0; // Evaluated later
            } elseif ($this->isMinProfitRule($rule)) {
                $minProfit = (float) ($rule->value ?? $rule->min_profit ?? 0);
                $entry['effect'] = 0;
            }

            $appliedRules[] = $entry;
        }

        // ── Subtotal before guardrails ───────────────────────
        $subtotal = $netRate + $markupAmount + $serviceFee + $surcharge - $discount;

        // ── 4. FR-BRP-007: Expired subscription surcharge ────
        $expiredSurcharge = false;
        $subscriptionStatus = $context['subscription_status'] ?? 'active';
        if ($subscriptionStatus === 'expired' || $subscriptionStatus === 'cancelled') {
            $policy = ExpiredPlanPolicy::getPolicy($context['plan_slug'] ?? null);
            if ($policy) {
                $extraAmount = $policy->apply($netRate, $subtotal);
                $surcharge += $extraAmount;
                $subtotal += $extraAmount;
                $expiredSurcharge = true;
                $appliedRules[] = [
                    'rule_id' => $policy->id,
                    'name'    => $policy->reason_label ?? 'Expired plan surcharge',
                    'type'    => 'expired_plan_surcharge',
                    'value'   => (float) $policy->value,
                    'effect'  => $extraAmount,
                ];
            }
        }

        // ── 5. FR-BRP-004: Guardrails ───────────────────────
        if ($minPrice !== null && $subtotal < $minPrice) {
            $adjustment = $minPrice - $subtotal;
            $guardrailAdjustments[] = ['type' => 'min_price', 'original' => $subtotal, 'adjusted' => $minPrice, 'diff' => $adjustment];
            $subtotal = $minPrice;
        }

        if ($minProfit !== null) {
            $currentProfit = $subtotal - $netRate;
            if ($currentProfit < $minProfit) {
                $adjustment = $minProfit - $currentProfit;
                $guardrailAdjustments[] = ['type' => 'min_profit', 'required' => $minProfit, 'actual' => $currentProfit, 'added' => $adjustment];
                $subtotal += $adjustment;
            }
        }

        $preRoundingTotal = $subtotal;

        // ── 6. FR-BRP-005: Rounding ─────────────────────────
        $roundingPolicyName = null;
        $roundingPolicy = RoundingPolicy::getForCurrency($currency);
        if ($roundingPolicy) {
            $subtotal = $roundingPolicy->apply($subtotal);
            $roundingPolicyName = "{$roundingPolicy->method}/{$roundingPolicy->precision}/{$roundingPolicy->step}";
        } else {
            $subtotal = round($subtotal, 2);
        }

        // ── 7. FR-BRP-006: Store breakdown ──────────────────
        return PricingBreakdown::create([
            'account_id'             => $accountId,
            'entity_type'            => $entityType,
            'entity_id'              => $entityId,
            'correlation_id'         => $correlationId,
            'carrier_code'           => $context['carrier_code'] ?? '',
            'service_code'           => $context['service_code'] ?? '',
            'origin_country'         => $context['origin_country'] ?? null,
            'destination_country'    => $context['destination_country'] ?? null,
            'weight'                 => $context['weight'] ?? null,
            'zone'                   => $context['zone'] ?? null,
            'shipment_type'          => $context['shipment_type'] ?? 'standard',
            'net_rate'               => $netRate,
            'markup_amount'          => $markupAmount,
            'service_fee'            => $serviceFee,
            'surcharge'              => $surcharge,
            'discount'               => $discount,
            'tax_amount'             => 0,
            'pre_rounding_total'     => $preRoundingTotal,
            'retail_rate'            => $subtotal,
            'rule_set_id'            => $ruleSet?->id,
            'rule_set_version'       => $ruleSet?->version,
            'applied_rules'          => $appliedRules,
            'guardrail_adjustments'  => $guardrailAdjustments ?: null,
            'rounding_policy'        => $roundingPolicyName,
            'currency'               => $currency,
            'plan_slug'              => $context['plan_slug'] ?? null,
            'expired_plan_surcharge' => $expiredSurcharge,
        ]);
    }

    /**
     * FR-BRP-008: Match rules by conditions and resolve priority/cumulative.
     */
    private function matchRules(Collection $rules, array $context): Collection
    {
        $matched = $rules->filter(function ($rule) use ($context) {
            // BRP-style rule with conditions JSON
            if (method_exists($rule, 'matchesContext') && $rule->conditions !== null) {
                return $rule->matchesContext($context);
            }
            // Legacy RT-style rule with individual fields
            if (method_exists($rule, 'matches')) {
                return $rule->matches($context);
            }
            return true;
        })->sortBy(fn($r) => $r->priority ?? 100);

        // Group by type category; for non-cumulative, keep highest priority only
        $result = collect();
        $typesSeen = [];

        foreach ($matched as $rule) {
            $category = $this->getRuleCategory($rule);
            $isCumulative = $rule->is_cumulative ?? false;

            if (!$isCumulative && isset($typesSeen[$category])) {
                continue; // Skip lower priority rule of same category
            }

            $result->push($rule);
            $typesSeen[$category] = true;
        }

        return $result;
    }

    /**
     * Get effect amount from a rule (supports both BRP and legacy RT models).
     */
    private function getRuleEffect($rule, float $netRate): float
    {
        // BRP-style: uses calculateEffect method
        if (method_exists($rule, 'calculateEffect') && isset($rule->type)) {
            return $rule->calculateEffect($netRate);
        }

        // Legacy RT-style: manual calculation from individual columns
        $effect = 0;
        if ($rule->markup_percentage ?? false) {
            $effect += $netRate * ((float) $rule->markup_percentage / 100);
        }
        if ($rule->markup_fixed ?? false) {
            $effect += (float) $rule->markup_fixed;
        }
        return $effect;
    }

    private function getRuleCategory($rule): string
    {
        if (isset($rule->type)) return $rule->type;
        return 'markup'; // Legacy default
    }

    private function isMarkupRule($rule): bool
    {
        if (isset($rule->type)) return str_starts_with($rule->type, 'markup_');
        return (bool) ($rule->markup_percentage ?? $rule->markup_fixed ?? false);
    }

    private function isServiceFeeRule($rule): bool
    {
        if (isset($rule->type)) return str_starts_with($rule->type, 'service_fee_');
        return (bool) ($rule->service_fee_fixed ?? $rule->service_fee_percentage ?? false);
    }

    private function isDiscountRule($rule): bool
    {
        return isset($rule->type) && str_starts_with($rule->type, 'discount_');
    }

    private function isSurchargeRule($rule): bool
    {
        return isset($rule->type) && $rule->type === 'surcharge';
    }

    private function isMinPriceRule($rule): bool
    {
        if (isset($rule->type) && $rule->type === 'min_price') return true;
        return (bool) ($rule->min_retail_price ?? false);
    }

    private function isMinProfitRule($rule): bool
    {
        if (isset($rule->type) && $rule->type === 'min_profit') return true;
        return (bool) ($rule->min_profit ?? false);
    }

    private function getLegacyType($rule): string
    {
        if ($rule->markup_percentage ?? false) return 'markup_percentage';
        if ($rule->markup_fixed ?? false) return 'markup_fixed';
        return 'legacy';
    }

    // ── Admin helpers ────────────────────────────────────────

    public function getBreakdown(string $entityType, string $entityId): ?PricingBreakdown
    {
        return PricingBreakdown::forEntity($entityType, $entityId)->latest()->first();
    }

    public function getBreakdownByCorrelation(string $correlationId): ?PricingBreakdown
    {
        return PricingBreakdown::where('correlation_id', $correlationId)->first();
    }

    public function listBreakdowns(string $accountId, int $perPage = 20)
    {
        return PricingBreakdown::where('account_id', $accountId)->orderByDesc('created_at')->paginate($perPage);
    }

    // ── Rule Set management ──────────────────────────────────

    public function createRuleSet(?string $accountId, string $name, ?string $createdBy = null): PricingRuleSet
    {
        return PricingRuleSet::create([
            'account_id' => $accountId, 'name' => $name,
            'version' => 1, 'status' => PricingRuleSet::STATUS_DRAFT,
            'created_by' => $createdBy,
        ]);
    }

    public function activateRuleSet(string $ruleSetId): PricingRuleSet
    {
        $set = PricingRuleSet::findOrFail($ruleSetId);
        $set->activate();
        return $set->fresh();
    }

    // ── Rounding policies ────────────────────────────────────

    public function setRoundingPolicy(string $currency, string $method, int $precision = 2, float $step = 0.01): RoundingPolicy
    {
        return RoundingPolicy::updateOrCreate(
            ['currency' => $currency],
            ['method' => $method, 'precision' => $precision, 'step' => $step, 'is_active' => true]
        );
    }

    // ── Expired plan policies ────────────────────────────────

    public function setExpiredPlanPolicy(?string $planSlug, string $type, float $value, string $label = 'Expired plan surcharge'): ExpiredPlanPolicy
    {
        return ExpiredPlanPolicy::updateOrCreate(
            ['plan_slug' => $planSlug],
            ['policy_type' => $type, 'value' => $value, 'reason_label' => $label, 'is_active' => true]
        );
    }
}
