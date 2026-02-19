<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RateController — FR-RT-001→012 API endpoints
 */
class RateController extends Controller
{
    public function __construct(protected RateService $rateService) {}

    // ── FR-RT-001: Fetch Rates for Shipment ──────────────────────
    public function fetchRates(Request $request, string $shipmentId): JsonResponse
    {
        $carrier = $request->query('carrier');

        $quote = $this->rateService->fetchRates(
            $request->user()->account_id, $shipmentId, $request->user(), $carrier
        );

        return response()->json(['data' => $quote]);
    }

    // ── FR-RT-007: Re-price (expired quote) ──────────────────────
    public function reprice(Request $request, string $shipmentId): JsonResponse
    {
        $carrier = $request->query('carrier');

        $quote = $this->rateService->reprice(
            $request->user()->account_id, $shipmentId, $request->user(), $carrier
        );

        return response()->json(['data' => $quote]);
    }

    // ── FR-RT-010: Select Rate Option ────────────────────────────
    public function selectOption(Request $request, string $quoteId): JsonResponse
    {
        $data = $request->validate([
            'option_id' => 'nullable|uuid',
            'strategy'  => 'nullable|string|in:cheapest,fastest,best_value',
        ]);

        $quote = $this->rateService->selectOption(
            $request->user()->account_id, $quoteId,
            $data['option_id'] ?? null,
            $data['strategy'] ?? 'cheapest',
            $request->user()
        );

        return response()->json(['data' => $quote]);
    }

    // ── FR-RT-005/011: Get Quote Details ─────────────────────────
    public function showQuote(Request $request, string $quoteId): JsonResponse
    {
        $result = $this->rateService->getQuote(
            $request->user()->account_id, $quoteId, $request->user()
        );

        return response()->json(['data' => $result]);
    }

    // ── FR-RT-008: Pricing Rules CRUD ────────────────────────────

    public function listRules(Request $request): JsonResponse
    {
        $rules = $this->rateService->listPricingRules($request->user()->account_id);
        return response()->json(['data' => $rules]);
    }

    public function createRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                       => 'required|string|max:200',
            'description'                => 'nullable|string',
            'carrier_code'               => 'nullable|string|max:50',
            'service_code'               => 'nullable|string|max:50',
            'origin_country'             => 'nullable|string|size:2',
            'destination_country'        => 'nullable|string|size:2',
            'destination_zone'           => 'nullable|string|max:50',
            'shipment_type'              => 'nullable|in:any,domestic,international',
            'min_weight'                 => 'nullable|numeric|min:0',
            'max_weight'                 => 'nullable|numeric|min:0',
            'store_id'                   => 'nullable|uuid',
            'is_cod'                     => 'nullable|boolean',
            'markup_type'                => 'required|in:percentage,fixed,both',
            'markup_percentage'          => 'nullable|numeric|min:0|max:999',
            'markup_fixed'               => 'nullable|numeric|min:0',
            'min_profit'                 => 'nullable|numeric|min:0',
            'min_retail_price'           => 'nullable|numeric|min:0',
            'max_retail_price'           => 'nullable|numeric|min:0',
            'service_fee_fixed'          => 'nullable|numeric|min:0',
            'service_fee_percentage'     => 'nullable|numeric|min:0|max:100',
            'rounding_mode'              => 'nullable|in:none,ceil,floor,round',
            'rounding_precision'         => 'nullable|numeric|min:0.01',
            'is_expired_surcharge'       => 'nullable|boolean',
            'expired_surcharge_percentage' => 'nullable|numeric|min:0|max:100',
            'priority'                   => 'nullable|integer|min:1|max:9999',
            'is_active'                  => 'nullable|boolean',
            'is_default'                 => 'nullable|boolean',
        ]);

        $rule = $this->rateService->createPricingRule(
            $request->user()->account_id, $data, $request->user()
        );

        return response()->json(['data' => $rule], 201);
    }

    public function updateRule(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'                       => 'nullable|string|max:200',
            'markup_type'                => 'nullable|in:percentage,fixed,both',
            'markup_percentage'          => 'nullable|numeric|min:0|max:999',
            'markup_fixed'               => 'nullable|numeric|min:0',
            'min_profit'                 => 'nullable|numeric|min:0',
            'min_retail_price'           => 'nullable|numeric|min:0',
            'service_fee_fixed'          => 'nullable|numeric|min:0',
            'service_fee_percentage'     => 'nullable|numeric|min:0|max:100',
            'rounding_mode'              => 'nullable|in:none,ceil,floor,round',
            'priority'                   => 'nullable|integer|min:1|max:9999',
            'is_active'                  => 'nullable|boolean',
        ]);

        $rule = $this->rateService->updatePricingRule(
            $request->user()->account_id, $id, $data, $request->user()
        );

        return response()->json(['data' => $rule]);
    }

    public function deleteRule(Request $request, string $id): JsonResponse
    {
        $this->rateService->deletePricingRule(
            $request->user()->account_id, $id, $request->user()
        );

        return response()->json(['message' => 'تم حذف قاعدة التسعير بنجاح.']);
    }
}
