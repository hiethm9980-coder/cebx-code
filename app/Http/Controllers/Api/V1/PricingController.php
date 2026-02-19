<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PricingRuleSet;
use App\Services\PricingEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PricingController — FR-BRP-001→008
 */
class PricingController extends Controller
{
    public function __construct(private PricingEngineService $engine) {}

    // ═══════════════ FR-BRP-001: Calculate Price ═════════════

    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'net_rate'       => 'required|numeric|min:0',
            'carrier_code'   => 'required|string',
            'service_code'   => 'required|string',
            'origin_country' => 'nullable|string|size:2',
            'destination_country' => 'nullable|string|size:2',
            'weight'         => 'nullable|numeric|min:0',
            'zone'           => 'nullable|string',
            'shipment_type'  => 'nullable|string',
            'store_id'       => 'nullable|string',
            'currency'       => 'nullable|string|size:3',
        ]);

        $context = array_merge($data, [
            'plan_slug'           => $request->user()->account->plan_slug ?? null,
            'subscription_status' => $request->user()->account->subscription_status ?? 'active',
        ]);

        $breakdown = $this->engine->calculatePrice(
            $request->user()->account_id,
            $data['net_rate'],
            $context
        );

        return response()->json(['status' => 'success', 'data' => $breakdown]);
    }

    // ═══════════════ FR-BRP-006: Get Breakdown ═══════════════

    public function getBreakdown(string $entityType, string $entityId): JsonResponse
    {
        $breakdown = $this->engine->getBreakdown($entityType, $entityId);
        if (!$breakdown) return response()->json(['status' => 'error', 'message' => 'Breakdown not found'], 404);
        return response()->json(['status' => 'success', 'data' => $breakdown]);
    }

    public function listBreakdowns(Request $request): JsonResponse
    {
        $data = $this->engine->listBreakdowns($request->user()->account_id);
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // ═══════════════ FR-BRP-008: Rule Sets ═══════════════════

    public function createRuleSet(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:200']);
        $set = $this->engine->createRuleSet($request->user()->account_id, $data['name'], $request->user()->id);
        return response()->json(['status' => 'success', 'data' => $set], 201);
    }

    public function activateRuleSet(string $ruleSetId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->engine->activateRuleSet($ruleSetId)]);
    }

    public function listRuleSets(Request $request): JsonResponse
    {
        $sets = PricingRuleSet::where(fn($q) => $q->where('account_id', $request->user()->account_id)->orWhereNull('account_id'))
            ->withCount('rules')
            ->orderByDesc('created_at')
            ->get();
        return response()->json(['status' => 'success', 'data' => $sets]);
    }

    public function getRuleSet(string $ruleSetId): JsonResponse
    {
        $set = PricingRuleSet::with('rules')->findOrFail($ruleSetId);
        return response()->json(['status' => 'success', 'data' => $set]);
    }

    // ═══════════════ FR-BRP-005: Rounding ════════════════════

    public function setRounding(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currency'  => 'required|string|size:3',
            'method'    => 'required|in:up,down,nearest,none',
            'precision' => 'nullable|integer|min:0|max:4',
            'step'      => 'nullable|numeric|min:0.0001',
        ]);
        $policy = $this->engine->setRoundingPolicy($data['currency'], $data['method'], $data['precision'] ?? 2, $data['step'] ?? 0.01);
        return response()->json(['status' => 'success', 'data' => $policy]);
    }

    // ═══════════════ FR-BRP-007: Expired Plan Policy ═════════

    public function setExpiredPolicy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_slug'   => 'nullable|string',
            'policy_type' => 'required|in:surcharge_percent,surcharge_fixed,markup_override',
            'value'       => 'required|numeric|min:0',
            'reason_label' => 'nullable|string',
        ]);
        $policy = $this->engine->setExpiredPlanPolicy(
            $data['plan_slug'] ?? null, $data['policy_type'], $data['value'], $data['reason_label'] ?? 'Expired plan surcharge'
        );
        return response()->json(['status' => 'success', 'data' => $policy]);
    }
}
