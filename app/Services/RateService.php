<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\PricingRule;
use App\Models\RateOption;
use App\Models\RateQuote;
use App\Models\Shipment;
use App\Models\User;
use App\Exceptions\BusinessException;
use App\Services\Carriers\CarrierRateAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RateService — FR-RT-001→012 (12 requirements)
 *
 * FR-RT-001: Fetch net rates from carriers (DHL)
 * FR-RT-002: Calculate retail rates with markup
 * FR-RT-003: Markup types (%, fixed, both, min profit, min retail)
 * FR-RT-004: Rounding per currency/rule
 * FR-RT-005: Store pricing breakdown per quote/shipment
 * FR-RT-006: Display options with badges (cheapest/fastest/best value)
 * FR-RT-007: Quote TTL & re-pricing on expiry
 * FR-RT-008: Conditional pricing rules (destination/weight/service/store)
 * FR-RT-009: Expired subscription pricing surcharge
 * FR-RT-010: Manual/auto best offer selection
 * FR-RT-011: Pricing detail visibility based on RBAC
 * FR-RT-012: KYC-based service restriction
 */
class RateService
{
    public function __construct(
        protected CarrierRateAdapter $carrierAdapter,
        protected PricingEngine $pricingEngine,
        protected AuditService $auditService,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // FR-RT-001: Fetch Rates for Shipment
    // ═══════════════════════════════════════════════════════════════

    public function fetchRates(string $accountId, string $shipmentId, User $performer, ?string $carrierCode = null): RateQuote
    {
        $shipment = Shipment::where('account_id', $accountId)->where('id', $shipmentId)->with('parcels')->firstOrFail();
        $account  = Account::findOrFail($accountId);

        // Shipment must be in draft or validated state
        if (!in_array($shipment->status, [Shipment::STATUS_DRAFT, Shipment::STATUS_VALIDATED, Shipment::STATUS_RATED])) {
            throw new BusinessException('لا يمكن جلب الأسعار في هذه الحالة.', 'ERR_INVALID_STATE_FOR_RATES', 422);
        }

        return DB::transaction(function () use ($shipment, $account, $performer, $carrierCode) {
            // Build request context
            $context = $this->buildContext($shipment, $carrierCode);

            // Create quote record
            $quote = RateQuote::create([
                'account_id'         => $account->id,
                'shipment_id'        => $shipment->id,
                'origin_country'     => $shipment->sender_country,
                'origin_city'        => $shipment->sender_city,
                'destination_country'=> $shipment->recipient_country,
                'destination_city'   => $shipment->recipient_city,
                'total_weight'       => $shipment->total_weight,
                'chargeable_weight'  => $shipment->chargeable_weight,
                'parcels_count'      => $shipment->parcels_count,
                'is_cod'             => $shipment->is_cod,
                'cod_amount'         => $shipment->cod_amount,
                'is_insured'         => $shipment->is_insured,
                'insurance_value'    => $shipment->insurance_amount,
                'currency'           => $shipment->currency ?? 'SAR',
                'status'             => RateQuote::STATUS_PENDING,
                'expires_at'         => now()->addMinutes(RateQuote::DEFAULT_TTL_MINUTES),
                'correlation_id'     => 'RQ-' . Str::upper(Str::random(12)),
                'requested_by'       => $performer->id,
                'request_metadata'   => $context,
            ]);

            try {
                // FR-RT-001: Fetch net rates from carrier(s)
                $rawRates = $this->carrierAdapter->fetchRates($context);

                if (empty($rawRates)) {
                    $quote->update(['status' => RateQuote::STATUS_FAILED, 'error_message' => 'لا توجد خدمات متاحة.']);
                    throw new BusinessException('لا توجد خدمات شحن متاحة لهذه الوجهة.', 'ERR_NO_RATES_AVAILABLE', 422);
                }

                // Load pricing rules
                $rules = PricingRule::active()->forAccount($account->id)->byPriority()->get();
                $isExpired = ($account->status ?? 'active') === 'expired' || ($account->subscription_status ?? 'active') === 'expired';

                // FR-RT-012: KYC-based filtering
                $kycStatus = $account->kyc_status ?? 'unverified';
                $filterIntl = $kycStatus !== 'verified';

                // Process each carrier rate option
                $options = [];
                foreach ($rawRates as $raw) {
                    // FR-RT-012: Filter international services for non-KYC accounts
                    if ($filterIntl && $shipment->is_international && in_array($raw['service_code'] ?? '', ['express_9_00'])) {
                        continue;
                    }

                    $optionContext = array_merge($context, [
                        'carrier_code' => $raw['carrier_code'],
                        'service_code' => $raw['service_code'],
                    ]);

                    // FR-RT-002: Calculate retail rate
                    $pricing = $this->pricingEngine->calculate(
                        (float) $raw['total_net_rate'],
                        $optionContext,
                        $rules,
                        $isExpired
                    );

                    $options[] = RateOption::create([
                        'rate_quote_id'              => $quote->id,
                        'carrier_code'               => $raw['carrier_code'],
                        'carrier_name'               => $raw['carrier_name'],
                        'service_code'               => $raw['service_code'],
                        'service_name'               => $raw['service_name'],
                        'net_rate'                   => $raw['net_rate'],
                        'fuel_surcharge'             => $raw['fuel_surcharge'] ?? 0,
                        'other_surcharges'           => $raw['other_surcharges'] ?? 0,
                        'total_net_rate'             => $raw['total_net_rate'],
                        'markup_amount'              => $pricing['markup_amount'],
                        'service_fee'                => $pricing['service_fee'],
                        'retail_rate_before_rounding' => $pricing['retail_rate_before_rounding'],
                        'retail_rate'                => $pricing['retail_rate'],
                        'profit_margin'              => $pricing['profit_margin'],
                        'currency'                   => $shipment->currency ?? 'SAR',
                        'estimated_days_min'         => $raw['estimated_days_min'] ?? null,
                        'estimated_days_max'         => $raw['estimated_days_max'] ?? null,
                        'pricing_rule_id'            => $pricing['pricing_rule_id'],
                        'pricing_breakdown'          => $pricing['pricing_breakdown'],
                        'rule_evaluation_log'        => $pricing['rule_evaluation_log'],
                        'is_available'               => $raw['is_available'] ?? true,
                        'unavailable_reason'         => $raw['unavailable_reason'] ?? null,
                    ]);
                }

                // FR-RT-006: Assign badges
                $this->assignBadges($options);

                // Update quote
                $quote->update([
                    'status'        => RateQuote::STATUS_COMPLETED,
                    'options_count' => count($options),
                ]);

                // Update shipment status
                if ($shipment->status === Shipment::STATUS_DRAFT || $shipment->status === Shipment::STATUS_VALIDATED) {
                    $shipment->update(['status' => Shipment::STATUS_RATED]);
                }

            } catch (BusinessException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $quote->update([
                    'status'        => RateQuote::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);
                throw new BusinessException('فشل في جلب الأسعار: ' . $e->getMessage(), 'ERR_RATE_FETCH_FAILED', 500);
            }

            $this->auditService->info(
                $account->id, $performer->id,
                'rate.fetched', AuditLog::CATEGORY_ACCOUNT,
                'RateQuote', $quote->id,
                null,
                ['shipment_id' => $shipment->id, 'options' => count($options), 'correlation' => $quote->correlation_id]
            );

            return $quote->load('options');
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-RT-010: Select Rate Option (manual or auto)
    // ═══════════════════════════════════════════════════════════════

    public function selectOption(string $accountId, string $quoteId, ?string $optionId, string $strategy, User $performer): RateQuote
    {
        $quote = RateQuote::where('account_id', $accountId)->where('id', $quoteId)->with('options')->firstOrFail();

        // FR-RT-007: Check TTL
        if ($quote->isExpired()) {
            throw new BusinessException('انتهت صلاحية عرض الأسعار. يرجى إعادة جلب الأسعار.', 'ERR_QUOTE_EXPIRED', 422);
        }

        if ($quote->status !== RateQuote::STATUS_COMPLETED) {
            throw new BusinessException('عرض الأسعار غير مكتمل.', 'ERR_QUOTE_NOT_COMPLETED', 422);
        }

        $option = null;

        if ($optionId) {
            // Manual selection
            $option = $quote->options->firstWhere('id', $optionId);
            if (!$option || !$option->is_available) {
                throw new BusinessException('الخيار المحدد غير متاح.', 'ERR_OPTION_NOT_AVAILABLE', 422);
            }
        } else {
            // Auto selection by strategy (FR-RT-010)
            $available = $quote->options->where('is_available', true);
            $option = match ($strategy) {
                'cheapest' => $available->sortBy('retail_rate')->first(),
                'fastest'  => $available->sortBy('estimated_days_min')->first(),
                'best_value' => $available->sortByDesc('is_best_value')->sortBy('retail_rate')->first(),
                default    => $available->sortBy('retail_rate')->first(),
            };

            if (!$option) {
                throw new BusinessException('لا توجد خيارات متاحة.', 'ERR_NO_OPTIONS', 422);
            }
        }

        // Update quote
        $quote->update([
            'selected_option_id' => $option->id,
            'status'             => RateQuote::STATUS_SELECTED,
        ]);

        // Update shipment with selected carrier/rate info
        if ($quote->shipment_id) {
            Shipment::where('id', $quote->shipment_id)->update([
                'carrier_code'  => $option->carrier_code,
                'carrier_name'  => $option->carrier_name,
                'service_code'  => $option->service_code,
                'service_name'  => $option->service_name,
                'shipping_rate' => $option->total_net_rate,
                'platform_fee'  => $option->service_fee,
                'profit_margin' => $option->profit_margin,
                'total_charge'  => $option->retail_rate,
                'estimated_delivery_at' => $option->estimated_delivery_at ?? now()->addDays($option->estimated_days_max ?? 5),
            ]);
        }

        $this->auditService->info(
            $accountId, $performer->id,
            'rate.selected', AuditLog::CATEGORY_ACCOUNT,
            'RateQuote', $quote->id,
            null,
            ['option_id' => $option->id, 'carrier' => $option->carrier_code, 'service' => $option->service_code, 'retail_rate' => $option->retail_rate]
        );

        return $quote->fresh(['options', 'selectedOption']);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-RT-007: Re-price (when quote expired)
    // ═══════════════════════════════════════════════════════════════

    public function reprice(string $accountId, string $shipmentId, User $performer, ?string $carrierCode = null): RateQuote
    {
        // Simply fetch new rates (old ones will be marked expired)
        $oldQuotes = RateQuote::where('account_id', $accountId)
            ->where('shipment_id', $shipmentId)
            ->where('status', RateQuote::STATUS_COMPLETED)
            ->get();

        foreach ($oldQuotes as $q) {
            $q->update(['status' => RateQuote::STATUS_EXPIRED, 'is_expired' => true]);
        }

        return $this->fetchRates($accountId, $shipmentId, $performer, $carrierCode);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-RT-005/011: Get Quote Details (with RBAC visibility)
    // ═══════════════════════════════════════════════════════════════

    public function getQuote(string $accountId, string $quoteId, User $performer): array
    {
        $quote = RateQuote::where('account_id', $accountId)
            ->where('id', $quoteId)
            ->with('options')
            ->firstOrFail();

        // FR-RT-011: Control breakdown visibility
        $canViewFinancial = $performer->is_owner || $performer->hasPermission('rates:view_breakdown');

        $options = $quote->options->map(function ($opt) use ($canViewFinancial) {
            $data = $opt->toArray();

            if ($canViewFinancial) {
                // Show full breakdown including hidden fields
                $data['net_rate']          = $opt->net_rate;
                $data['fuel_surcharge']    = $opt->fuel_surcharge;
                $data['other_surcharges']  = $opt->other_surcharges;
                $data['total_net_rate']    = $opt->total_net_rate;
                $data['markup_amount']     = $opt->markup_amount;
                $data['profit_margin']     = $opt->profit_margin;
                $data['pricing_breakdown'] = $opt->pricing_breakdown;
            } else {
                // FR-RT-011: Show only retail rate, service name, delivery estimate
                unset($data['pricing_breakdown'], $data['rule_evaluation_log'], $data['pricing_rule_id']);
            }

            $data['badges']            = $opt->badges();
            $data['delivery_estimate'] = $opt->deliveryEstimate();

            return $data;
        });

        return [
            'quote'   => $quote,
            'options' => $options,
            'is_expired' => $quote->isExpired(),
            'expires_in_seconds' => $quote->expires_at ? max(0, now()->diffInSeconds($quote->expires_at, false)) : null,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Pricing Rules CRUD (FR-RT-008)
    // ═══════════════════════════════════════════════════════════════

    public function listPricingRules(string $accountId): \Illuminate\Database\Eloquent\Collection
    {
        return PricingRule::forAccount($accountId)->byPriority()->get();
    }

    public function createPricingRule(string $accountId, array $data, User $performer): PricingRule
    {
        if (!$performer->is_owner && !$performer->hasPermission('rates:manage_rules')) {
            throw BusinessException::permissionDenied();
        }

        $rule = PricingRule::create(array_merge($data, ['account_id' => $accountId]));

        $this->auditService->info(
            $accountId, $performer->id,
            'pricing_rule.created', AuditLog::CATEGORY_ACCOUNT,
            'PricingRule', $rule->id,
            null, ['name' => $rule->name, 'priority' => $rule->priority]
        );

        return $rule;
    }

    public function updatePricingRule(string $accountId, string $ruleId, array $data, User $performer): PricingRule
    {
        if (!$performer->is_owner && !$performer->hasPermission('rates:manage_rules')) {
            throw BusinessException::permissionDenied();
        }

        $rule = PricingRule::where('account_id', $accountId)->where('id', $ruleId)->firstOrFail();
        $old = $rule->toArray();
        $rule->update($data);

        $this->auditService->info(
            $accountId, $performer->id,
            'pricing_rule.updated', AuditLog::CATEGORY_ACCOUNT,
            'PricingRule', $rule->id,
            $old, $rule->toArray()
        );

        return $rule->fresh();
    }

    public function deletePricingRule(string $accountId, string $ruleId, User $performer): void
    {
        if (!$performer->is_owner && !$performer->hasPermission('rates:manage_rules')) {
            throw BusinessException::permissionDenied();
        }

        $rule = PricingRule::where('account_id', $accountId)->where('id', $ruleId)->firstOrFail();
        $rule->delete();

        $this->auditService->warning(
            $accountId, $performer->id,
            'pricing_rule.deleted', AuditLog::CATEGORY_ACCOUNT,
            'PricingRule', $ruleId,
            ['name' => $rule->name], null
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function buildContext(Shipment $shipment, ?string $carrierCode): array
    {
        return [
            'carrier_code'        => $carrierCode,
            'origin_country'      => $shipment->sender_country,
            'origin_city'         => $shipment->sender_city,
            'destination_country' => $shipment->recipient_country,
            'destination_city'    => $shipment->recipient_city,
            'total_weight'        => (float) $shipment->total_weight,
            'chargeable_weight'   => (float) ($shipment->chargeable_weight ?? $shipment->total_weight),
            'parcels_count'       => $shipment->parcels_count,
            'is_cod'              => $shipment->is_cod,
            'is_international'    => $shipment->is_international,
            'store_id'            => $shipment->store_id,
            'shipment_type'       => $shipment->is_international ? 'international' : 'domestic',
        ];
    }

    /**
     * FR-RT-006: Assign badges (cheapest, fastest, best value, recommended).
     */
    private function assignBadges(array $options): void
    {
        if (empty($options)) return;

        $available = collect($options)->where('is_available', true);
        if ($available->isEmpty()) return;

        $cheapest = $available->sortBy('retail_rate')->first();
        $fastest  = $available->sortBy('estimated_days_min')->first();

        $cheapest->update(['is_cheapest' => true]);
        $fastest->update(['is_fastest' => true]);

        // Best value = best balance of price and speed
        $bestValue = $available->sortBy(fn($o) => ($o->retail_rate * 0.6) + (($o->estimated_days_min ?? 5) * 10 * 0.4))->first();
        $bestValue->update(['is_best_value' => true]);

        // Recommended = best value unless same as cheapest
        if ($bestValue->id !== $cheapest->id) {
            $bestValue->update(['is_recommended' => true]);
        } else {
            $cheapest->update(['is_recommended' => true]);
        }
    }
}
