<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\SLAEngineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * CBEX GROUP — SLA Controller
 */
class SLAController extends Controller
{
    public function __construct(protected SLAEngineService $sla) {}

    /**
     * SLA Dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->sla->dashboard($request->user()->account_id)]);
    }

    /**
     * Check SLA for a specific shipment
     */
    public function check(Request $request, string $id): JsonResponse
    {
        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);
        return response()->json(['data' => $this->sla->checkSLA($shipment)]);
    }

    /**
     * Scan all active shipments for SLA breaches
     */
    public function scanBreaches(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->sla->scanBreaches()]);
    }

    /**
     * Get at-risk shipments (approaching SLA breach)
     */
    public function atRisk(Request $request): JsonResponse
    {
        $shipments = Shipment::where('account_id', $request->user()->account_id)
            ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
            ->get();

        $atRisk = [];
        foreach ($shipments as $s) {
            $sla = $this->sla->checkSLA($s);
            if ($sla['phase_sla']['percentage'] >= 75 || $sla['total_sla']['percentage'] >= 75) {
                $atRisk[] = [
                    'shipment' => [
                        'id' => $s->id,
                        'tracking_number' => $s->tracking_number,
                        'status' => $s->status,
                        'receiver_name' => $s->receiver_name,
                    ],
                    'sla' => $sla,
                ];
            }
        }

        usort($atRisk, fn($a, $b) => $b['sla']['phase_sla']['percentage'] <=> $a['sla']['phase_sla']['percentage']);

        return response()->json(['data' => ['at_risk' => $atRisk, 'count' => count($atRisk)]]);
    }
}
