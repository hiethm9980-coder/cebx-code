<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DeliveryAssignment;
use App\Models\ProofOfDelivery;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * DRV Module — Drivers & Last Mile Delivery
 * السائقين وتوصيل الميل الأخير
 */
class DriverController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // DRIVERS CRUD
    // ══════════════════════════════════════════════════════════════

    public function index(Request $r): JsonResponse
    {
        $q = Driver::where('account_id', $r->user()->account_id)->with('branch:id,name,city');

        if ($r->status) $q->where('status', $r->status);
        if ($r->branch_id) $q->where('branch_id', $r->branch_id);
        if ($r->available) $q->available();
        if ($r->search) $q->where(fn($q2) => $q2->where('name', 'like', "%{$r->search}%")
            ->orWhere('phone', 'like', "%{$r->search}%")
            ->orWhere('vehicle_plate', 'like', "%{$r->search}%"));

        return response()->json(['data' => $q->orderBy('name')->paginate($r->per_page ?? 25)]);
    }

    public function store(Request $r): JsonResponse
    {
        $v = $r->validate([
            'name'           => 'required|string|max:200',
            'phone'          => 'required|string|max:30',
            'email'          => 'nullable|email',
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date|after:today',
            'vehicle_type'   => 'nullable|string|max:50',
            'vehicle_plate'  => 'nullable|string|max:30',
            'id_number'      => 'nullable|string|max:30',
            'nationality'    => 'nullable|string|size:2',
            'branch_id'      => 'nullable|uuid|exists:branches,id',
            'zones'          => 'nullable|array',
        ]);

        $v['account_id'] = $r->user()->account_id;
        $driver = Driver::create($v);

        return response()->json(['data' => $driver, 'message' => 'تم إضافة السائق بنجاح'], 201);
    }

    public function show(string $id): JsonResponse
    {
        $driver = Driver::with([
            'branch:id,name,city',
            'activeAssignments.shipment:id,tracking_number,status',
        ])->findOrFail($id);

        $driver->success_rate = $driver->getSuccessRate();

        return response()->json(['data' => $driver]);
    }

    public function update(Request $r, string $id): JsonResponse
    {
        $driver = Driver::findOrFail($id);
        $driver->update($r->only([
            'name', 'phone', 'email', 'license_number', 'license_expiry',
            'vehicle_type', 'vehicle_plate', 'id_number', 'nationality',
            'branch_id', 'status', 'zones', 'photo_url',
        ]));
        return response()->json(['data' => $driver, 'message' => 'تم تحديث بيانات السائق']);
    }

    public function destroy(string $id): JsonResponse
    {
        $driver = Driver::findOrFail($id);
        if ($driver->activeAssignments()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف سائق لديه مهام نشطة'], 422);
        }
        $driver->delete();
        return response()->json(['message' => 'تم حذف السائق']);
    }

    public function updateLocation(Request $r, string $id): JsonResponse
    {
        $r->validate(['latitude' => 'required|numeric', 'longitude' => 'required|numeric']);
        $driver = Driver::findOrFail($id);
        $driver->updateLocation($r->latitude, $r->longitude);
        return response()->json(['message' => 'تم تحديث الموقع']);
    }

    public function updateStatus(Request $r, string $id): JsonResponse
    {
        $r->validate(['status' => 'required|in:available,on_duty,on_delivery,off_duty,suspended']);
        Driver::findOrFail($id)->update(['status' => $r->status]);
        return response()->json(['message' => 'تم تحديث حالة السائق']);
    }

    // ══════════════════════════════════════════════════════════════
    // DELIVERY ASSIGNMENTS
    // ══════════════════════════════════════════════════════════════

    public function assignments(Request $r): JsonResponse
    {
        $q = DeliveryAssignment::where('account_id', $r->user()->account_id)
            ->with(['driver:id,name,phone', 'shipment:id,tracking_number,status,recipient_name,recipient_phone']);

        if ($r->driver_id) $q->where('driver_id', $r->driver_id);
        if ($r->status) $q->where('status', $r->status);
        if ($r->type) $q->where('type', $r->type);
        if ($r->date) $q->whereDate('scheduled_at', $r->date);
        if ($r->active) $q->whereNotIn('status', ['delivered', 'failed', 'returned', 'cancelled']);

        return response()->json(['data' => $q->orderByDesc('created_at')->paginate($r->per_page ?? 25)]);
    }

    public function assign(Request $r): JsonResponse
    {
        $v = $r->validate([
            'shipment_id'          => 'required|uuid|exists:shipments,id',
            'driver_id'            => 'required|uuid|exists:drivers,id',
            'type'                 => 'required|in:pickup,delivery,return',
            'scheduled_at'         => 'nullable|date',
            'special_instructions' => 'nullable|string|max:1000',
            'pickup_lat'           => 'nullable|numeric',
            'pickup_lng'           => 'nullable|numeric',
            'delivery_lat'         => 'nullable|numeric',
            'delivery_lng'         => 'nullable|numeric',
        ]);

        $v['account_id']         = $r->user()->account_id;
        $v['assignment_number']  = DeliveryAssignment::generateNumber();
        $v['status']             = 'assigned';

        // Calculate distance if coordinates provided
        if ($r->pickup_lat && $r->delivery_lat) {
            $v['distance_km'] = $this->haversineDistance(
                $r->pickup_lat, $r->pickup_lng, $r->delivery_lat, $r->delivery_lng
            );
            $v['estimated_minutes'] = max(10, round($v['distance_km'] * 3)); // ~20km/h avg
        }

        $assignment = DeliveryAssignment::create($v);

        // Update driver status
        Driver::find($r->driver_id)?->update(['status' => 'on_delivery']);

        // Update shipment
        Shipment::find($r->shipment_id)?->update(['driver_id' => $r->driver_id]);

        return response()->json(['data' => $assignment->load(['driver', 'shipment']), 'message' => 'تم تعيين مهمة التوصيل'], 201);
    }

    public function showAssignment(string $id): JsonResponse
    {
        return response()->json(['data' => DeliveryAssignment::with([
            'driver', 'shipment', 'branch', 'proofOfDelivery',
        ])->findOrFail($id)]);
    }

    public function updateAssignmentStatus(Request $r, string $id): JsonResponse
    {
        $r->validate([
            'status'         => 'required|in:accepted,rejected,en_route_pickup,picked_up,en_route_delivery,arrived,attempting,delivered,failed,returned,cancelled',
            'failure_reason' => 'nullable|string|max:500',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $assignment = DeliveryAssignment::findOrFail($id);
        $updates = ['status' => $r->status];

        match ($r->status) {
            'accepted'   => $updates['accepted_at'] = now(),
            'picked_up'  => $updates['picked_up_at'] = now(),
            'delivered'  => $updates['delivered_at'] = now(),
            'failed'     => array_merge($updates, ['failure_reason' => $r->failure_reason]),
            'rejected'   => null,
            default      => null,
        };

        if ($r->notes) $updates['delivery_notes'] = $r->notes;
        $assignment->update($updates);

        // Update driver & shipment status accordingly
        if ($r->status === 'delivered') {
            Driver::find($assignment->driver_id)?->update(['status' => 'available']);
            Driver::find($assignment->driver_id)?->increment('total_deliveries');
            Driver::find($assignment->driver_id)?->increment('successful_deliveries');
            Shipment::find($assignment->shipment_id)?->update(['status' => 'delivered', 'actual_delivery_at' => now()]);
        } elseif ($r->status === 'failed') {
            Driver::find($assignment->driver_id)?->increment('total_deliveries');
            // Check if retry possible
            if ($assignment->attempt_number < $assignment->max_attempts) {
                $assignment->update(['attempt_number' => $assignment->attempt_number + 1]);
            }
        } elseif (in_array($r->status, ['rejected', 'cancelled'])) {
            Driver::find($assignment->driver_id)?->update(['status' => 'available']);
        } elseif ($r->status === 'en_route_delivery') {
            Shipment::find($assignment->shipment_id)?->update(['status' => 'out_for_delivery']);
        }

        return response()->json(['data' => $assignment, 'message' => 'تم تحديث حالة التوصيل']);
    }

    // ══════════════════════════════════════════════════════════════
    // PROOF OF DELIVERY (POD)
    // ══════════════════════════════════════════════════════════════

    public function submitPod(Request $r, string $assignmentId): JsonResponse
    {
        $r->validate([
            'pod_type'           => 'required|in:signature,otp,photo,pin,biometric',
            'recipient_name'     => 'required|string|max:200',
            'recipient_relation' => 'nullable|string|max:100',
            'recipient_id_number'=> 'nullable|string|max:30',
            'signature_data'     => 'required_if:pod_type,signature|nullable|string',
            'otp_code'           => 'required_if:pod_type,otp|nullable|string|max:10',
            'photo'              => 'required_if:pod_type,photo|nullable|file|image|max:10240',
            'latitude'           => 'nullable|numeric',
            'longitude'          => 'nullable|numeric',
            'notes'              => 'nullable|string',
        ]);

        $assignment = DeliveryAssignment::findOrFail($assignmentId);

        $podData = [
            'assignment_id'       => $assignmentId,
            'shipment_id'         => $assignment->shipment_id,
            'pod_type'            => $r->pod_type,
            'recipient_name'      => $r->recipient_name,
            'recipient_relation'  => $r->recipient_relation,
            'recipient_id_number' => $r->recipient_id_number,
            'latitude'            => $r->latitude,
            'longitude'           => $r->longitude,
            'captured_at'         => now(),
            'notes'               => $r->notes,
        ];

        // Handle POD type specifics
        if ($r->pod_type === 'signature') {
            $podData['signature_data'] = $r->signature_data;
        } elseif ($r->pod_type === 'otp') {
            $podData['otp_code'] = $r->otp_code;
            $podData['otp_verified'] = true; // TODO: Actual verification
        } elseif ($r->pod_type === 'photo' && $r->hasFile('photo')) {
            $path = $r->file('photo')->store("pod/{$assignmentId}", 'public');
            $podData['photo_url'] = $path;
        }

        $pod = ProofOfDelivery::create($podData);

        // Auto-mark as delivered
        $assignment->update(['status' => 'delivered', 'delivered_at' => now()]);
        Shipment::find($assignment->shipment_id)?->update([
            'status' => 'delivered',
            'actual_delivery_at' => now(),
            'pod_status' => 'confirmed',
        ]);

        // Update driver stats
        $driver = Driver::find($assignment->driver_id);
        if ($driver) {
            $driver->increment('total_deliveries');
            $driver->increment('successful_deliveries');
            $driver->update(['status' => 'available']);
        }

        return response()->json(['data' => $pod, 'message' => 'تم تأكيد التسليم بنجاح'], 201);
    }

    public function showPod(string $assignmentId): JsonResponse
    {
        $pod = ProofOfDelivery::where('assignment_id', $assignmentId)->firstOrFail();
        return response()->json(['data' => $pod]);
    }

    // Generate OTP for delivery verification
    public function generateOtp(Request $r, string $assignmentId): JsonResponse
    {
        $assignment = DeliveryAssignment::findOrFail($assignmentId);
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP (in practice, send SMS to recipient)
        $assignment->update(['metadata' => array_merge($assignment->metadata ?? [], ['otp' => $otp, 'otp_expires' => now()->addMinutes(15)->toIso8601String()])]);

        // TODO: Send SMS to recipient
        // $shipment = Shipment::find($assignment->shipment_id);
        // SmsService::send($shipment->recipient_phone, "رمز التحقق لاستلام شحنتك: {$otp}");

        return response()->json(['message' => 'تم إرسال رمز التحقق', 'otp_preview' => $otp]); // Remove otp_preview in production
    }

    // ══════════════════════════════════════════════════════════════
    // DRIVER STATS & ANALYTICS
    // ══════════════════════════════════════════════════════════════

    public function stats(Request $r): JsonResponse
    {
        $aid = $r->user()->account_id;
        $driverQ = Driver::where('account_id', $aid);
        $assignQ = DeliveryAssignment::where('account_id', $aid);

        return response()->json(['data' => [
            'total_drivers'       => (clone $driverQ)->count(),
            'available'           => (clone $driverQ)->where('status', 'available')->count(),
            'on_delivery'         => (clone $driverQ)->where('status', 'on_delivery')->count(),
            'off_duty'            => (clone $driverQ)->where('status', 'off_duty')->count(),
            'suspended'           => (clone $driverQ)->where('status', 'suspended')->count(),
            'total_assignments'   => (clone $assignQ)->count(),
            'active_assignments'  => (clone $assignQ)->whereNotIn('status', ['delivered', 'failed', 'returned', 'cancelled'])->count(),
            'delivered_today'     => (clone $assignQ)->where('status', 'delivered')->whereDate('delivered_at', today())->count(),
            'failed_today'        => (clone $assignQ)->where('status', 'failed')->whereDate('created_at', today())->count(),
            'avg_delivery_time'   => round((clone $assignQ)->where('status', 'delivered')->whereNotNull('accepted_at')->whereNotNull('delivered_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, accepted_at, delivered_at)) as avg')->value('avg') ?? 0),
            'success_rate'        => (function() use ($assignQ) {
                $total = (clone $assignQ)->whereIn('status', ['delivered', 'failed'])->count();
                $success = (clone $assignQ)->where('status', 'delivered')->count();
                return $total > 0 ? round(($success / $total) * 100, 1) : 100;
            })(),
        ]]);
    }

    public function leaderboard(Request $r): JsonResponse
    {
        $drivers = Driver::where('account_id', $r->user()->account_id)
            ->where('total_deliveries', '>', 0)
            ->orderByDesc('successful_deliveries')
            ->limit(20)->get()
            ->map(fn($d) => [
                'id' => $d->id, 'name' => $d->name, 'phone' => $d->phone,
                'total' => $d->total_deliveries, 'success' => $d->successful_deliveries,
                'rate' => $d->getSuccessRate(), 'rating' => $d->rating,
            ]);
        return response()->json(['data' => $drivers]);
    }

    // ── Haversine distance (km) ──────────────────────────────────
    private function haversineDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
