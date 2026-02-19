<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TariffRule;
use App\Models\ShipmentCharge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TariffController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $q = TariffRule::where('account_id', $r->user()->account_id);
        if ($r->origin_country) $q->where(fn($q) => $q->where('origin_country', $r->origin_country)->orWhere('origin_country', '*'));
        if ($r->destination_country) $q->where(fn($q) => $q->where('destination_country', $r->destination_country)->orWhere('destination_country', '*'));
        if ($r->shipment_type) $q->where(fn($q) => $q->where('shipment_type', $r->shipment_type)->orWhere('shipment_type', 'any'));
        if ($r->active_only) $q->active();
        if ($r->search) $q->where('name', 'like', "%{$r->search}%");
        return response()->json(['data' => $q->orderBy('priority', 'desc')->paginate($r->per_page ?? 25)]);
    }

    public function store(Request $r): JsonResponse
    {
        $v = $r->validate([
            'name' => 'required|string|max:200',
            'origin_country' => 'required|string|max:3',
            'destination_country' => 'required|string|max:3',
            'origin_city' => 'nullable|string|max:100',
            'destination_city' => 'nullable|string|max:100',
            'shipment_type' => 'required|in:air,sea,land,express,any',
            'carrier_code' => 'nullable|string|max:50',
            'service_level' => 'nullable|string|max:50',
            'incoterm_code' => 'nullable|string|max:3',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric',
            'min_volume' => 'nullable|numeric',
            'max_volume' => 'nullable|numeric',
            'pricing_unit' => 'required|in:kg,cbm,piece,container,flat',
            'base_price' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'minimum_charge' => 'nullable|numeric|min:0',
            'fuel_surcharge_percent' => 'nullable|numeric|min:0|max:100',
            'security_surcharge' => 'nullable|numeric|min:0',
            'peak_season_surcharge' => 'nullable|numeric|min:0|max:100',
            'insurance_rate' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'priority' => 'nullable|integer',
            'conditions' => 'nullable|array',
        ]);
        $v['account_id'] = $r->user()->account_id;
        return response()->json(['data' => TariffRule::create($v), 'message' => 'تم إنشاء قاعدة التعرفة'], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => TariffRule::findOrFail($id)]);
    }

    public function update(Request $r, string $id): JsonResponse
    {
        $t = TariffRule::findOrFail($id);
        $t->update($r->except(['account_id']));
        return response()->json(['data' => $t, 'message' => 'تم تحديث التعرفة']);
    }

    public function destroy(string $id): JsonResponse
    {
        TariffRule::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف التعرفة']);
    }

    /**
     * Calculate tariff for given parameters — محرك حساب التعرفة
     */
    public function calculate(Request $r): JsonResponse
    {
        $r->validate([
            'origin_country' => 'required|string|max:3',
            'destination_country' => 'required|string|max:3',
            'weight' => 'required|numeric|min:0.001',
            'volume' => 'nullable|numeric',
            'shipment_type' => 'nullable|in:air,sea,land,express',
            'declared_value' => 'nullable|numeric|min:0',
            'carrier_code' => 'nullable|string',
            'incoterm_code' => 'nullable|string|size:3',
        ]);

        $rules = TariffRule::where('account_id', $r->user()->account_id)
            ->active()
            ->forRoute($r->origin_country, $r->destination_country)
            ->when($r->shipment_type, fn($q) => $q->where(fn($q2) => $q2->where('shipment_type', $r->shipment_type)->orWhere('shipment_type', 'any')))
            ->when($r->carrier_code, fn($q) => $q->where(fn($q2) => $q2->where('carrier_code', $r->carrier_code)->orWhereNull('carrier_code')))
            ->where('min_weight', '<=', $r->weight)
            ->where('max_weight', '>=', $r->weight)
            ->orderBy('priority', 'desc')
            ->get();

        if ($rules->isEmpty()) {
            return response()->json(['data' => [], 'message' => 'لا توجد تعرفات مطابقة — أضف قواعد تعرفة أولاً']);
        }

        $results = $rules->map(function ($rule) use ($r) {
            $calc = $rule->calculate($r->weight, $r->volume, $r->declared_value ?? 0);
            return array_merge($calc, [
                'tariff_id' => $rule->id,
                'tariff_name' => $rule->name,
                'carrier_code' => $rule->carrier_code,
                'service_level' => $rule->service_level,
                'shipment_type' => $rule->shipment_type,
            ]);
        });

        return response()->json(['data' => $results->sortBy('total')->values()]);
    }

    /**
     * Get charges breakdown for a shipment
     */
    public function shipmentCharges(string $shipmentId): JsonResponse
    {
        $charges = ShipmentCharge::where('shipment_id', $shipmentId)->orderBy('charge_type')->get();
        return response()->json(['data' => [
            'charges' => $charges,
            'total_billable' => $charges->where('is_billable', true)->sum('amount'),
            'total_taxable' => $charges->where('is_taxable', true)->sum('amount'),
            'grand_total' => $charges->sum('amount'),
        ]]);
    }

    public function addCharge(Request $r, string $shipmentId): JsonResponse
    {
        $v = $r->validate([
            'charge_type' => 'required|string',
            'description' => 'nullable|string|max:300',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|size:3',
            'is_billable' => 'nullable|boolean',
            'is_taxable' => 'nullable|boolean',
        ]);
        $v['shipment_id'] = $shipmentId;
        $v['created_by'] = $r->user()->id;
        return response()->json(['data' => ShipmentCharge::create($v), 'message' => 'تم إضافة الرسم'], 201);
    }

    public function removeCharge(string $shipmentId, string $chargeId): JsonResponse
    {
        ShipmentCharge::where('shipment_id', $shipmentId)->findOrFail($chargeId)->delete();
        return response()->json(['message' => 'تم حذف الرسم']);
    }
}
