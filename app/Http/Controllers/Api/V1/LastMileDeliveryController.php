<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Driver;
use App\Models\DeliveryAssignment;
use App\Models\ProofOfDelivery;
use App\Services\StatusTransitionService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * CBEX GROUP — Last Mile Delivery Controller
 *
 * Manages driver assignment, out-for-delivery,
 * proof of delivery (signature/OTP/photo), and delivery completion.
 */
class LastMileDeliveryController extends Controller
{
    public function __construct(
        protected StatusTransitionService $statusEngine,
        protected AuditService $audit,
    ) {}

    /**
     * Get delivery dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $accountId = $request->user()->account_id;
        $today = now()->startOfDay();

        return response()->json(['data' => [
            'pending_assignment' => Shipment::where('account_id', $accountId)
                ->where('status', 'cleared_import')->count(),
            'out_for_delivery' => Shipment::where('account_id', $accountId)
                ->where('status', 'out_for_delivery')->count(),
            'delivered_today' => Shipment::where('account_id', $accountId)
                ->where('status', 'delivered')
                ->where('delivered_at', '>=', $today)->count(),
            'failed_today' => Shipment::where('account_id', $accountId)
                ->where('status', 'failed_delivery')
                ->where('status_updated_at', '>=', $today)->count(),
            'available_drivers' => Driver::where('account_id', $accountId)
                ->where('status', 'available')
                ->where('is_active', true)->count(),
            'active_drivers' => Driver::where('account_id', $accountId)
                ->where('status', 'on_delivery')->count(),
        ]]);
    }

    /**
     * List pending deliveries (ready for assignment)
     */
    public function pendingDeliveries(Request $request): JsonResponse
    {
        $shipments = Shipment::where('account_id', $request->user()->account_id)
            ->whereIn('status', ['cleared_import', 'ready_for_pickup'])
            ->with(['deliveryAssignment.driver:id,name,phone,vehicle_number'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json(['data' => $shipments]);
    }

    /**
     * Assign driver to shipment
     */
    public function assignDriver(Request $request, string $shipmentId): JsonResponse
    {
        $data = $request->validate([
            'driver_id' => 'required|uuid|exists:drivers,id',
            'scheduled_date' => 'nullable|date',
            'scheduled_time_from' => 'nullable|date_format:H:i',
            'scheduled_time_to' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
            'priority' => 'in:normal,high,urgent',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($shipmentId);
        $driver = Driver::where('account_id', $request->user()->account_id)->findOrFail($data['driver_id']);

        // Check driver availability
        $activeDeliveries = DeliveryAssignment::where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();

        if ($activeDeliveries >= ($driver->max_deliveries ?? 15)) {
            return response()->json(['message' => 'السائق لديه الحد الأقصى من التوصيلات'], 422);
        }

        // Create assignment
        $assignment = DeliveryAssignment::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $request->user()->account_id,
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'assigned_by' => $request->user()->id,
            'status' => 'assigned',
            'priority' => $data['priority'] ?? 'normal',
            'scheduled_date' => $data['scheduled_date'] ?? now()->toDateString(),
            'scheduled_time_from' => $data['scheduled_time_from'] ?? null,
            'scheduled_time_to' => $data['scheduled_time_to'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_at' => now(),
        ]);

        // Update driver status
        $driver->update(['status' => 'on_delivery']);

        // Transition shipment
        $this->statusEngine->transition($shipment, 'out_for_delivery', [
            'user_id' => $request->user()->id,
            'notes' => "تم تعيين السائق: {$driver->name}",
        ]);

        $this->audit->log('delivery.assigned', $assignment);

        return response()->json([
            'data' => $assignment->load(['driver:id,name,phone,vehicle_number', 'shipment:id,tracking_number']),
            'message' => "تم تعيين السائق {$driver->name}",
        ], 201);
    }

    /**
     * Record proof of delivery
     */
    public function recordPOD(Request $request, string $shipmentId): JsonResponse
    {
        $data = $request->validate([
            'pod_type' => 'required|in:signature,otp,photo,combined',
            'signature_data' => 'required_if:pod_type,signature,combined|nullable|string',
            'otp_code' => 'required_if:pod_type,otp|nullable|string|max:10',
            'photo_url' => 'required_if:pod_type,photo,combined|nullable|string',
            'recipient_name' => 'required|string|max:200',
            'recipient_relation' => 'nullable|string|max:100',
            'recipient_id_number' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($shipmentId);

        if ($shipment->status !== 'out_for_delivery') {
            return response()->json(['message' => 'الشحنة ليست خارج للتسليم'], 422);
        }

        // Verify OTP if provided
        if ($data['pod_type'] === 'otp' && isset($data['otp_code'])) {
            // TODO: Verify OTP code against stored code
        }

        $pod = ProofOfDelivery::create([
            'id' => Str::uuid()->toString(),
            'shipment_id' => $shipment->id,
            'assignment_id' => $shipment->deliveryAssignment?->id,
            'pod_type' => $data['pod_type'],
            'signature_data' => $data['signature_data'] ?? null,
            'photo_url' => $data['photo_url'] ?? null,
            'recipient_name' => $data['recipient_name'],
            'recipient_relation' => $data['recipient_relation'] ?? null,
            'recipient_id_number' => $data['recipient_id_number'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? null,
            'delivered_at' => now(),
            'delivered_by' => $request->user()->id,
        ]);

        // Transition to delivered
        $this->statusEngine->transition($shipment, 'delivered', [
            'user_id' => $request->user()->id,
            'notes' => "تم التسليم — المستلم: {$data['recipient_name']}",
            'location' => ($data['latitude'] ?? null) ? "{$data['latitude']},{$data['longitude']}" : null,
        ]);

        // Update driver status
        $assignment = $shipment->deliveryAssignment;
        if ($assignment) {
            $assignment->update(['status' => 'completed', 'completed_at' => now()]);
            $remaining = DeliveryAssignment::where('driver_id', $assignment->driver_id)
                ->where('status', 'assigned')->count();
            if ($remaining === 0) {
                Driver::where('id', $assignment->driver_id)->update(['status' => 'available']);
            }
        }

        $this->audit->log('delivery.completed', $shipment);

        return response()->json([
            'data' => $pod,
            'message' => 'تم تسجيل التسليم بنجاح ✅',
        ], 201);
    }

    /**
     * Record failed delivery attempt
     */
    public function failedDelivery(Request $request, string $shipmentId): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|in:no_answer,wrong_address,refused,closed,other',
            'notes' => 'nullable|string|max:500',
            'reschedule_date' => 'nullable|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($shipmentId);

        $reasonLabels = [
            'no_answer' => 'لا يوجد رد',
            'wrong_address' => 'عنوان خاطئ',
            'refused' => 'رفض الاستلام',
            'closed' => 'الموقع مغلق',
            'other' => $data['notes'] ?? 'سبب آخر',
        ];

        $this->statusEngine->transition($shipment, 'failed_delivery', [
            'user_id' => $request->user()->id,
            'notes' => "فشل التسليم: {$reasonLabels[$data['reason']]}",
            'location' => ($data['latitude'] ?? null) ? "{$data['latitude']},{$data['longitude']}" : null,
        ]);

        // Auto-reschedule if date provided
        if (isset($data['reschedule_date'])) {
            $assignment = $shipment->deliveryAssignment;
            if ($assignment) {
                $assignment->update([
                    'status' => 'rescheduled',
                    'scheduled_date' => $data['reschedule_date'],
                    'attempt_count' => ($assignment->attempt_count ?? 0) + 1,
                ]);
            }
        }

        return response()->json([
            'data' => $shipment->fresh(),
            'message' => 'تم تسجيل فشل التسليم',
        ]);
    }

    /**
     * Get driver assignments
     */
    public function driverAssignments(Request $request, string $driverId): JsonResponse
    {
        $assignments = DeliveryAssignment::where('driver_id', $driverId)
            ->with(['shipment:id,tracking_number,receiver_name,receiver_phone,receiver_address,receiver_city,status'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn($q) => $q->where('scheduled_date', $request->date))
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_time_from')
            ->paginate(20);

        return response()->json(['data' => $assignments]);
    }
}
