<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RiskScore;
use App\Models\RouteSuggestion;
use App\Models\Shipment;
use App\Models\TariffRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * AIR Module — AI & Risk Intelligence
 * الذكاء الاصطناعي والمخاطر
 */
class RiskController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // RISK SCORING
    // ══════════════════════════════════════════════════════════════

    /**
     * Score a shipment — تقييم مخاطر الشحنة
     */
    public function score(Request $r, string $shipmentId): JsonResponse
    {
        $shipment = Shipment::findOrFail($shipmentId);
        $risk = RiskScore::calculateForShipment($shipment);
        return response()->json(['data' => $risk, 'message' => 'تم تقييم المخاطر']);
    }

    /**
     * Get existing score
     */
    public function show(string $shipmentId): JsonResponse
    {
        $risk = RiskScore::where('shipment_id', $shipmentId)->latest()->first();
        if (!$risk) return response()->json(['message' => 'لم يتم تقييم المخاطر بعد'], 404);
        return response()->json(['data' => $risk]);
    }

    /**
     * Batch scoring — تقييم مخاطر عدة شحنات
     */
    public function batchScore(Request $r): JsonResponse
    {
        $r->validate(['shipment_ids' => 'required|array|min:1|max:50', 'shipment_ids.*' => 'uuid|exists:shipments,id']);

        $results = [];
        foreach ($r->shipment_ids as $sid) {
            $shipment = Shipment::find($sid);
            if ($shipment) {
                $results[] = RiskScore::calculateForShipment($shipment);
            }
        }

        return response()->json(['data' => $results, 'message' => "تم تقييم {count($results)} شحنة"]);
    }

    /**
     * Dashboard — لوحة المخاطر
     */
    public function dashboard(Request $r): JsonResponse
    {
        $aid = $r->user()->account_id;
        $shipmentIds = Shipment::where('account_id', $aid)->pluck('id');
        $q = RiskScore::whereIn('shipment_id', $shipmentIds);

        return response()->json(['data' => [
            'total_scored' => (clone $q)->count(),
            'by_level' => (clone $q)->selectRaw('risk_level, count(*) as count')->groupBy('risk_level')->pluck('count', 'risk_level'),
            'critical_shipments' => (clone $q)->where('risk_level', 'critical')->with('shipment:id,tracking_number,status,carrier_name')
                ->orderByDesc('overall_score')->limit(10)->get(),
            'high_risk_shipments' => (clone $q)->where('risk_level', 'high')->with('shipment:id,tracking_number,status')
                ->orderByDesc('overall_score')->limit(10)->get(),
            'avg_scores' => [
                'overall' => round((clone $q)->avg('overall_score') ?? 0, 1),
                'delay' => round((clone $q)->avg('delay_probability') ?? 0, 1),
                'damage' => round((clone $q)->avg('damage_probability') ?? 0, 1),
                'customs' => round((clone $q)->avg('customs_risk') ?? 0, 1),
                'fraud' => round((clone $q)->avg('fraud_risk') ?? 0, 1),
            ],
        ]]);
    }

    // ══════════════════════════════════════════════════════════════
    // SMART ROUTING
    // ══════════════════════════════════════════════════════════════

    /**
     * Get route suggestions — اقتراحات المسارات
     */
    public function suggestRoutes(Request $r): JsonResponse
    {
        $r->validate([
            'origin_country'      => 'required|string|size:2',
            'destination_country' => 'required|string|size:2',
            'origin_city'         => 'nullable|string|max:100',
            'destination_city'    => 'nullable|string|max:100',
            'weight'              => 'required|numeric|min:0.001',
            'volume'              => 'nullable|numeric',
            'declared_value'      => 'nullable|numeric|min:0',
            'priority'            => 'nullable|in:cost,speed,reliability',
            'shipment_id'         => 'nullable|uuid',
        ]);

        $priority = $r->priority ?? 'cost';

        // Get available tariffs
        $tariffs = TariffRule::where('account_id', $r->user()->account_id)
            ->active()
            ->forRoute($r->origin_country, $r->destination_country)
            ->where('min_weight', '<=', $r->weight)
            ->where('max_weight', '>=', $r->weight)
            ->orderBy('priority', 'desc')
            ->get();

        $suggestions = $tariffs->map(function ($tariff, $idx) use ($r, $priority) {
            $calc = $tariff->calculate($r->weight, $r->volume, $r->declared_value ?? 0);

            // Estimate transit days based on shipment type
            $transitDays = match ($tariff->shipment_type) {
                'air'     => rand(2, 5),
                'express' => rand(1, 3),
                'sea'     => rand(15, 35),
                'land'    => rand(5, 14),
                default   => rand(3, 10),
            };

            // Reliability score (simulated — in production, use historical data)
            $reliability = match ($tariff->shipment_type) {
                'air'     => rand(85, 97),
                'express' => rand(90, 99),
                'sea'     => rand(70, 90),
                'land'    => rand(75, 92),
                default   => rand(80, 95),
            };

            // Carbon footprint (kg CO2 per ton-km)
            $distanceFactor = match ($tariff->shipment_type) {
                'air'     => 0.6,
                'sea'     => 0.015,
                'land'    => 0.06,
                'express' => 0.7,
                default   => 0.1,
            };
            $carbon = round(($r->weight / 1000) * 5000 * $distanceFactor, 2); // Simplified

            // Score ranking based on priority
            $score = match ($priority) {
                'cost'        => 100 - ($calc['total'] / max($calc['total'], 1) * 50),
                'speed'       => 100 - ($transitDays * 3),
                'reliability' => $reliability,
            };

            return [
                'rank'              => $idx + 1,
                'carrier_code'      => $tariff->carrier_code ?? 'multi',
                'service_code'      => $tariff->service_level ?? 'standard',
                'transport_mode'    => $tariff->shipment_type,
                'tariff_name'       => $tariff->name,
                'route_legs'        => [
                    ['from' => $r->origin_country, 'to' => $r->destination_country,
                     'mode' => $tariff->shipment_type, 'carrier' => $tariff->carrier_code, 'days' => $transitDays],
                ],
                'estimated_days'    => $transitDays,
                'estimated_cost'    => $calc['total'],
                'cost_breakdown'    => $calc,
                'currency'          => $calc['currency'],
                'reliability_score' => $reliability,
                'carbon_footprint_kg' => $carbon,
                'priority_score'    => round($score, 1),
                'is_recommended'    => $idx === 0,
            ];
        })->sortByDesc('priority_score')->values();

        // Save to DB if shipment_id provided
        if ($r->shipment_id) {
            RouteSuggestion::where('shipment_id', $r->shipment_id)->delete();
            foreach ($suggestions as $idx => $s) {
                RouteSuggestion::create([
                    'shipment_id'       => $r->shipment_id,
                    'rank'              => $idx + 1,
                    'carrier_code'      => $s['carrier_code'],
                    'service_code'      => $s['service_code'],
                    'transport_mode'    => $s['transport_mode'],
                    'route_legs'        => $s['route_legs'],
                    'estimated_days'    => $s['estimated_days'],
                    'estimated_cost'    => $s['estimated_cost'],
                    'currency'          => $s['currency'],
                    'reliability_score' => $s['reliability_score'],
                    'carbon_footprint_kg' => $s['carbon_footprint_kg'],
                    'is_recommended'    => $s['is_recommended'],
                ]);
            }
        }

        return response()->json(['data' => $suggestions]);
    }

    /**
     * Select a route for shipment
     */
    public function selectRoute(Request $r, string $shipmentId, string $suggestionId): JsonResponse
    {
        // Deselect all
        RouteSuggestion::where('shipment_id', $shipmentId)->update(['is_selected' => false]);
        // Select chosen
        $route = RouteSuggestion::where('shipment_id', $shipmentId)->findOrFail($suggestionId);
        $route->update(['is_selected' => true]);

        // Update shipment
        Shipment::find($shipmentId)?->update([
            'carrier_code' => $route->carrier_code,
            'service_code' => $route->service_code,
            'shipment_type' => $route->transport_mode,
            'estimated_delivery_at' => now()->addDays($route->estimated_days),
        ]);

        return response()->json(['data' => $route, 'message' => 'تم اختيار المسار']);
    }

    // ══════════════════════════════════════════════════════════════
    // DELAY PREDICTION
    // ══════════════════════════════════════════════════════════════

    /**
     * Predict delay — توقع التأخير
     */
    public function predictDelay(Request $r, string $shipmentId): JsonResponse
    {
        $shipment = Shipment::findOrFail($shipmentId);

        // Factors affecting delay
        $factors = [];
        $delayProbability = 0;

        // Shipment type factor
        if ($shipment->shipment_type === 'sea') {
            $delayProbability += 25;
            $factors[] = ['factor' => 'sea_freight', 'impact' => 25, 'description_ar' => 'الشحن البحري عرضة لتأخيرات الطقس'];
        }

        // International
        if ($shipment->is_international) {
            $delayProbability += 15;
            $factors[] = ['factor' => 'international', 'impact' => 15, 'description_ar' => 'شحنة دولية — إجراءات جمركية'];
        }

        // DG
        if ($shipment->has_dangerous_goods) {
            $delayProbability += 20;
            $factors[] = ['factor' => 'dangerous_goods', 'impact' => 20, 'description_ar' => 'بضائع خطرة تحتاج تصاريح إضافية'];
        }

        // Heavy
        if ($shipment->chargeable_weight > 1000) {
            $delayProbability += 10;
            $factors[] = ['factor' => 'heavy_shipment', 'impact' => 10, 'description_ar' => 'شحنة ثقيلة — احتمال تأخير التحميل'];
        }

        // Weekend/Holiday
        if (now()->isWeekend()) {
            $delayProbability += 5;
            $factors[] = ['factor' => 'weekend', 'impact' => 5, 'description_ar' => 'عطلة نهاية الأسبوع'];
        }

        $delayProbability = min(100, $delayProbability);
        $predictedDays = max(0, round($delayProbability / 20));

        $eta = $shipment->estimated_delivery_at;
        $newEta = $eta ? $eta->addDays($predictedDays) : now()->addDays($predictedDays + 3);

        return response()->json(['data' => [
            'shipment_id'         => $shipmentId,
            'delay_probability'   => $delayProbability,
            'predicted_delay_days'=> $predictedDays,
            'original_eta'        => $eta?->toDateString(),
            'predicted_eta'       => $newEta->toDateString(),
            'risk_level'          => match (true) { $delayProbability >= 60 => 'high', $delayProbability >= 30 => 'medium', default => 'low' },
            'factors'             => $factors,
            'recommendations'     => $this->delayRecommendations($factors),
        ]]);
    }

    // ══════════════════════════════════════════════════════════════
    // FRAUD DETECTION
    // ══════════════════════════════════════════════════════════════

    /**
     * Fraud check — كشف الاحتيال
     */
    public function fraudCheck(Request $r, string $shipmentId): JsonResponse
    {
        $shipment = Shipment::findOrFail($shipmentId);
        $flags = []; $score = 0;

        // High declared value with low weight
        if ($shipment->declared_value > 50000 && $shipment->chargeable_weight < 5) {
            $score += 30;
            $flags[] = ['flag' => 'value_weight_mismatch', 'severity' => 'high', 'ar' => 'تناقض بين القيمة المعلنة والوزن'];
        }

        // Very high value
        if ($shipment->declared_value > 200000) {
            $score += 20;
            $flags[] = ['flag' => 'high_value', 'severity' => 'medium', 'ar' => 'قيمة مرتفعة جداً'];
        }

        // COD with international
        if ($shipment->is_cod && $shipment->is_international) {
            $score += 25;
            $flags[] = ['flag' => 'international_cod', 'severity' => 'high', 'ar' => 'دفع عند الاستلام لشحنة دولية — مخاطر عالية'];
        }

        // New customer with high value
        $customerShipments = Shipment::where('account_id', $shipment->account_id)->count();
        if ($customerShipments <= 3 && $shipment->declared_value > 10000) {
            $score += 15;
            $flags[] = ['flag' => 'new_customer_high_value', 'severity' => 'medium', 'ar' => 'عميل جديد بشحنة ذات قيمة عالية'];
        }

        $level = match (true) { $score >= 50 => 'critical', $score >= 30 => 'high', $score >= 15 => 'medium', default => 'low' };

        return response()->json(['data' => [
            'shipment_id' => $shipmentId,
            'fraud_score' => min(100, $score),
            'risk_level' => $level,
            'flags' => $flags,
            'action_required' => $level === 'critical' || $level === 'high',
            'recommendations' => match ($level) {
                'critical' => ['حجب الشحنة للمراجعة اليدوية', 'التحقق من هوية العميل', 'التحقق من عنوان المستلم'],
                'high'     => ['مراجعة يدوية قبل الشحن', 'التحقق من بيانات الدفع'],
                'medium'   => ['مراقبة الشحنة', 'تسجيل ملاحظة في الملف'],
                default    => ['لا حاجة لإجراء إضافي'],
            },
        ]]);
    }

    // ══════════════════════════════════════════════════════════════
    // ANALYTICS
    // ══════════════════════════════════════════════════════════════

    public function analytics(Request $r): JsonResponse
    {
        $aid = $r->user()->account_id;
        $shipments = Shipment::where('account_id', $aid);

        $totalShipments = (clone $shipments)->count();
        $delivered = (clone $shipments)->where('status', 'delivered')->count();
        $delayed = (clone $shipments)->where('status', 'exception')->count();
        $onTime = (clone $shipments)->where('status', 'delivered')
            ->whereNotNull('estimated_delivery_at')
            ->whereNotNull('actual_delivery_at')
            ->whereColumn('actual_delivery_at', '<=', 'estimated_delivery_at')->count();

        return response()->json(['data' => [
            'performance' => [
                'total_shipments' => $totalShipments,
                'delivered' => $delivered,
                'on_time_delivery_rate' => $delivered > 0 ? round(($onTime / $delivered) * 100, 1) : 0,
                'exception_rate' => $totalShipments > 0 ? round(($delayed / $totalShipments) * 100, 1) : 0,
            ],
            'by_type' => (clone $shipments)->selectRaw('shipment_type, count(*) as count, avg(total_charge) as avg_cost')
                ->groupBy('shipment_type')->get(),
            'by_carrier' => (clone $shipments)->selectRaw('carrier_code, count(*) as count')
                ->whereNotNull('carrier_code')->groupBy('carrier_code')
                ->orderByDesc('count')->limit(10)->get(),
            'monthly_trend' => (clone $shipments)->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")
                ->groupBy('month')->orderBy('month')->limit(12)->get(),
        ]]);
    }

    // ── Private helpers ──────────────────────────────────────────
    private function delayRecommendations(array $factors): array
    {
        $recs = [];
        foreach ($factors as $f) {
            $recs[] = match ($f['factor']) {
                'sea_freight' => 'فكر في الشحن الجوي للشحنات العاجلة',
                'international' => 'جهز المستندات الجمركية مسبقاً',
                'dangerous_goods' => 'تأكد من استكمال جميع التصاريح',
                'heavy_shipment' => 'اطلب حجز مسبق مع الناقل',
                'weekend' => 'اختر التسليم في أيام العمل',
                default => 'راقب الشحنة بانتظام',
            };
        }
        return array_unique($recs);
    }
}
