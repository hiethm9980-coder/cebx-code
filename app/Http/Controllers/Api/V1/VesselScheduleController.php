<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use App\Models\VesselSchedule;
use App\Models\Container;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * CBEX GROUP — Vessel & Schedule Controller
 *
 * Manages vessels, voyage schedules, and container assignments.
 */
class VesselScheduleController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    // ── Vessels ───────────────────────────────────────────────

    public function listVessels(Request $request): JsonResponse
    {
        $query = Vessel::where('account_id', $request->user()->account_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) $query->where('name', 'ilike', "%{$request->search}%");

        return response()->json(['data' => $query->withCount('schedules')->paginate(20)]);
    }

    public function createVessel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'imo_number' => 'nullable|string|max:20',
            'flag' => 'nullable|string|size:2',
            'vessel_type' => 'required|in:container,bulk,tanker,roro,general',
            'capacity_teu' => 'nullable|integer',
            'operator' => 'nullable|string|max:200',
        ]);

        $data['id'] = Str::uuid()->toString();
        $data['account_id'] = $request->user()->account_id;
        $data['status'] = 'active';

        $vessel = Vessel::create($data);
        $this->audit->log('vessel.created', $vessel);

        return response()->json(['data' => $vessel], 201);
    }

    public function showVessel(Request $request, string $id): JsonResponse
    {
        $vessel = Vessel::where('account_id', $request->user()->account_id)
            ->with(['schedules' => fn($q) => $q->orderBy('etd', 'desc')->limit(10)])
            ->findOrFail($id);

        return response()->json(['data' => $vessel]);
    }

    public function updateVessel(Request $request, string $id): JsonResponse
    {
        $vessel = Vessel::where('account_id', $request->user()->account_id)->findOrFail($id);
        $vessel->update($request->only(['name', 'imo_number', 'flag', 'vessel_type', 'capacity_teu', 'operator', 'status']));
        return response()->json(['data' => $vessel->fresh()]);
    }

    public function deleteVessel(Request $request, string $id): JsonResponse
    {
        $vessel = Vessel::where('account_id', $request->user()->account_id)->findOrFail($id);
        $vessel->delete();
        return response()->json(['message' => 'تم حذف السفينة']);
    }

    // ── Schedules ─────────────────────────────────────────────

    public function listSchedules(Request $request): JsonResponse
    {
        $query = VesselSchedule::where('account_id', $request->user()->account_id)
            ->with('vessel');

        if ($request->filled('vessel_id')) $query->where('vessel_id', $request->vessel_id);
        if ($request->filled('port_of_loading')) $query->where('port_of_loading', 'ilike', "%{$request->port_of_loading}%");
        if ($request->filled('port_of_discharge')) $query->where('port_of_discharge', 'ilike', "%{$request->port_of_discharge}%");
        if ($request->filled('from_date')) $query->where('etd', '>=', $request->from_date);
        if ($request->filled('to_date')) $query->where('etd', '<=', $request->to_date);
        if ($request->filled('status')) $query->where('status', $request->status);

        return response()->json(['data' => $query->orderBy('etd', 'desc')->paginate(20)]);
    }

    public function createSchedule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vessel_id' => 'required|uuid|exists:vessels,id',
            'voyage_number' => 'required|string|max:50',
            'port_of_loading' => 'required|string|max:100',
            'port_of_discharge' => 'required|string|max:100',
            'etd' => 'required|date',
            'eta' => 'required|date|after:etd',
            'transit_time_days' => 'nullable|integer',
            'cutoff_date' => 'nullable|date|before:etd',
            'notes' => 'nullable|string',
        ]);

        $data['id'] = Str::uuid()->toString();
        $data['account_id'] = $request->user()->account_id;
        $data['status'] = 'scheduled';

        $schedule = VesselSchedule::create($data);

        return response()->json(['data' => $schedule->load('vessel')], 201);
    }

    public function showSchedule(Request $request, string $id): JsonResponse
    {
        $schedule = VesselSchedule::where('account_id', $request->user()->account_id)
            ->with(['vessel', 'containers'])
            ->findOrFail($id);

        return response()->json(['data' => $schedule]);
    }

    public function updateSchedule(Request $request, string $id): JsonResponse
    {
        $schedule = VesselSchedule::where('account_id', $request->user()->account_id)->findOrFail($id);

        $data = $request->validate([
            'voyage_number' => 'string|max:50',
            'port_of_loading' => 'string|max:100',
            'port_of_discharge' => 'string|max:100',
            'etd' => 'date',
            'eta' => 'date',
            'status' => 'in:scheduled,departed,in_transit,arrived,completed,cancelled',
            'actual_departure' => 'nullable|date',
            'actual_arrival' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $schedule->update($data);
        $this->audit->log('schedule.updated', $schedule);

        return response()->json(['data' => $schedule->fresh()]);
    }

    public function deleteSchedule(Request $request, string $id): JsonResponse
    {
        $schedule = VesselSchedule::where('account_id', $request->user()->account_id)->findOrFail($id);
        $schedule->delete();
        return response()->json(['message' => 'تم حذف الجدول']);
    }

    /**
     * Search available schedules for a route
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'origin_port' => 'required|string',
            'destination_port' => 'required|string',
            'from_date' => 'required|date',
        ]);

        $schedules = VesselSchedule::where('account_id', $request->user()->account_id)
            ->where('port_of_loading', 'ilike', "%{$request->origin_port}%")
            ->where('port_of_discharge', 'ilike', "%{$request->destination_port}%")
            ->where('etd', '>=', $request->from_date)
            ->where('status', 'scheduled')
            ->with('vessel')
            ->orderBy('etd')
            ->limit(20)
            ->get();

        return response()->json(['data' => $schedules]);
    }

    /**
     * Get vessel schedule stats
     */
    public function scheduleStats(Request $request): JsonResponse
    {
        $accountId = $request->user()->account_id;

        return response()->json(['data' => [
            'total_vessels' => Vessel::where('account_id', $accountId)->count(),
            'active_voyages' => VesselSchedule::where('account_id', $accountId)
                ->whereIn('status', ['departed', 'in_transit'])->count(),
            'scheduled' => VesselSchedule::where('account_id', $accountId)
                ->where('status', 'scheduled')->where('etd', '>=', now())->count(),
            'completed_this_month' => VesselSchedule::where('account_id', $accountId)
                ->where('status', 'completed')
                ->where('actual_arrival', '>=', now()->startOfMonth())->count(),
            'ports' => VesselSchedule::where('account_id', $accountId)
                ->selectRaw('DISTINCT port_of_loading as port')->pluck('port')
                ->merge(
                    VesselSchedule::where('account_id', $accountId)
                        ->selectRaw('DISTINCT port_of_discharge as port')->pluck('port')
                )->unique()->values(),
        ]]);
    }
}
