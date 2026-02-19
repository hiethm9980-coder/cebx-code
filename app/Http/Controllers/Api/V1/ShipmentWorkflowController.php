<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\StatusTransitionService;
use App\Services\DutyCalculationService;
use App\Services\SLAEngineService;
use App\Services\AIDelayService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * CBEX GROUP — Shipment Workflow Controller
 *
 * Manages shipment lifecycle transitions:
 * Origin → Transit → Destination → Last Mile → Delivered
 */
class ShipmentWorkflowController extends Controller
{
    public function __construct(
        protected StatusTransitionService $engine,
        protected DutyCalculationService $duty,
        protected SLAEngineService $sla,
        protected AIDelayService $ai,
        protected AuditService $audit,
    ) {}

    /**
     * Get all valid statuses and transition rules
     */
    public function statuses(): JsonResponse
    {
        return response()->json(['data' => $this->engine->getAllStatuses()]);
    }

    /**
     * Get valid next statuses for a shipment
     */
    public function nextStatuses(Request $request, string $id): JsonResponse
    {
        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);
        return response()->json([
            'data' => [
                'current_status' => $shipment->status,
                'available_transitions' => $this->engine->getNextStatuses($shipment->status),
            ],
        ]);
    }

    /**
     * Transition shipment to a new status
     */
    public function transition(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:200',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'metadata' => 'nullable|array',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);

        $shipment = $this->engine->transition($shipment, $data['status'], [
            'user_id' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
            'location' => $data['location'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->audit->log('shipment.transitioned', $shipment, ['new_status' => $data['status']]);

        return response()->json([
            'data' => $shipment->load('trackingEvents'),
            'message' => 'تم تحديث الحالة بنجاح',
        ]);
    }

    // ── Phase-specific endpoints ─────────────────────────────

    /**
     * Origin Processing — receive at hub + weight verify
     */
    public function receiveAtOrigin(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'actual_weight' => 'required|numeric|min:0.1',
            'actual_length' => 'nullable|numeric',
            'actual_width' => 'nullable|numeric',
            'actual_height' => 'nullable|numeric',
            'branch_id' => 'required|uuid|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);

        // Recalculate chargeable weight
        $volumetric = 0;
        if (($data['actual_length'] ?? 0) && ($data['actual_width'] ?? 0) && ($data['actual_height'] ?? 0)) {
            $volumetric = ($data['actual_length'] * $data['actual_width'] * $data['actual_height']) / 5000;
        }
        $chargeableWeight = max($data['actual_weight'], $volumetric);

        $weightChanged = abs($chargeableWeight - ($shipment->chargeable_weight ?? 0)) > 0.1;

        $shipment->update([
            'actual_weight' => $data['actual_weight'],
            'chargeable_weight' => $chargeableWeight,
            'total_volume' => $volumetric,
        ]);

        // Transition
        $this->engine->transition($shipment, 'at_origin_hub', [
            'user_id' => $request->user()->id,
            'branch_id' => $data['branch_id'],
            'location' => 'مركز الأصل',
            'notes' => $weightChanged
                ? "تم التحقق من الوزن: {$data['actual_weight']} كجم (الوزن المحاسبي: {$chargeableWeight} كجم)"
                : $data['notes'],
        ]);

        return response()->json([
            'data' => $shipment->fresh(),
            'weight_changed' => $weightChanged,
            'chargeable_weight' => $chargeableWeight,
            'message' => 'تم استلام الشحنة في مركز الأصل',
        ]);
    }

    /**
     * Export Clearance
     */
    public function exportClearance(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'declaration_number' => 'nullable|string',
            'cleared' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);
        $newStatus = $data['cleared'] ? 'cleared_export' : 'held_customs';

        $this->engine->transition($shipment, $newStatus, [
            'user_id' => $request->user()->id,
            'notes' => $data['notes'] ?? ($data['cleared'] ? 'تم تخليص التصدير' : 'محتجز بالجمارك'),
        ]);

        return response()->json(['data' => $shipment->fresh(), 'message' => 'تم تحديث حالة التخليص']);
    }

    /**
     * Load to transit (assign vessel/flight/truck)
     */
    public function loadToTransit(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'vessel_id' => 'nullable|uuid',
            'container_id' => 'nullable|uuid',
            'flight_number' => 'nullable|string|max:20',
            'truck_number' => 'nullable|string|max:30',
            'eta' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);

        $shipment->update(array_filter([
            'vessel_id' => $data['vessel_id'] ?? null,
            'container_id' => $data['container_id'] ?? null,
            'flight_number' => $data['flight_number'] ?? null,
            'truck_number' => $data['truck_number'] ?? null,
            'eta' => $data['eta'] ?? null,
        ]));

        $this->engine->transition($shipment, 'in_transit', [
            'user_id' => $request->user()->id,
            'notes' => $data['notes'] ?? 'تم تحميل الشحنة',
            'vessel_id' => $data['vessel_id'] ?? null,
        ]);

        return response()->json(['data' => $shipment->fresh(), 'message' => 'الشحنة قيد الشحن']);
    }

    /**
     * Import Clearance + Duty Calculation
     */
    public function importClearance(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'cleared' => 'required|boolean',
            'declaration_number' => 'nullable|string',
            'inspection_required' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)
            ->with('items')
            ->findOrFail($id);

        // Calculate duties
        $dutyCalc = $this->duty->calculate([
            'origin_country' => $shipment->origin_country,
            'destination_country' => $shipment->destination_country,
            'declared_value' => $shipment->declared_value,
            'currency' => 'SAR',
            'incoterm' => $shipment->incoterm ?? 'DAP',
            'inspection_required' => $data['inspection_required'] ?? false,
            'items' => $shipment->items->map(fn($i) => [
                'hs_code' => $i->hs_code,
                'description' => $i->description,
                'quantity' => $i->quantity,
                'weight' => $i->weight,
                'value' => $i->value ?? 0,
            ])->toArray(),
        ]);

        // Store duty charges
        $this->duty->storeAsCharges($shipment->id, $dutyCalc);

        $newStatus = $data['cleared'] ? 'cleared_import' : 'held_customs_dest';

        $this->engine->transition($shipment, $newStatus, [
            'user_id' => $request->user()->id,
            'notes' => $data['notes'] ?? ($data['cleared'] ? 'تم تخليص الاستيراد' : 'محتجز بجمارك الوجهة'),
        ]);

        return response()->json([
            'data' => $shipment->fresh(),
            'duty_calculation' => $dutyCalc,
            'message' => 'تم تحديث حالة التخليص الجمركي',
        ]);
    }

    /**
     * Check SLA status for a shipment
     */
    public function checkSLA(Request $request, string $id): JsonResponse
    {
        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);
        return response()->json(['data' => $this->sla->checkSLA($shipment)]);
    }

    /**
     * Get AI delay prediction for a shipment
     */
    public function predictDelay(Request $request, string $id): JsonResponse
    {
        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);
        return response()->json(['data' => $this->ai->predict($shipment)]);
    }
}
