<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use App\Models\VesselSchedule;
use App\Models\Container;
use App\Models\ContainerShipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContainerController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // VESSELS
    // ══════════════════════════════════════════════════════════════

    public function vesselsIndex(Request $r): JsonResponse
    {
        $q = Vessel::where('account_id', $r->user()->account_id);
        if ($r->status) $q->where('status', $r->status);
        if ($r->vessel_type) $q->where('vessel_type', $r->vessel_type);
        if ($r->search) $q->where(fn($q) => $q->where('vessel_name', 'like', "%{$r->search}%")->orWhere('imo_number', 'like', "%{$r->search}%"));
        return response()->json(['data' => $q->with('activeSchedules')->orderBy('vessel_name')->paginate($r->per_page ?? 25)]);
    }

    public function vesselsStore(Request $r): JsonResponse
    {
        $v = $r->validate([
            'vessel_name' => 'required|string|max:200',
            'imo_number' => 'nullable|string|max:20',
            'mmsi' => 'nullable|string|max:20',
            'call_sign' => 'nullable|string|max:20',
            'flag' => 'nullable|string|max:3',
            'vessel_type' => 'required|in:container,bulk,tanker,roro,general',
            'operator' => 'nullable|string|max:200',
            'capacity_teu' => 'nullable|integer',
            'max_deadweight' => 'nullable|numeric',
        ]);
        $v['account_id'] = $r->user()->account_id;
        return response()->json(['data' => Vessel::create($v), 'message' => 'تم إضافة السفينة'], 201);
    }

    public function vesselsShow(string $id): JsonResponse
    {
        return response()->json(['data' => Vessel::with('schedules')->findOrFail($id)]);
    }

    public function vesselsUpdate(Request $r, string $id): JsonResponse
    {
        $v = Vessel::findOrFail($id);
        $v->update($r->only(['vessel_name', 'imo_number', 'mmsi', 'call_sign', 'flag', 'vessel_type', 'operator', 'capacity_teu', 'max_deadweight', 'status']));
        return response()->json(['data' => $v, 'message' => 'تم تحديث السفينة']);
    }

    public function vesselsDestroy(string $id): JsonResponse
    {
        Vessel::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف السفينة']);
    }

    // ══════════════════════════════════════════════════════════════
    // VESSEL SCHEDULES
    // ══════════════════════════════════════════════════════════════

    public function schedulesIndex(Request $r): JsonResponse
    {
        $q = VesselSchedule::where('account_id', $r->user()->account_id)->with('vessel:id,vessel_name,vessel_type');
        if ($r->vessel_id) $q->where('vessel_id', $r->vessel_id);
        if ($r->port_of_loading) $q->where('port_of_loading', $r->port_of_loading);
        if ($r->port_of_discharge) $q->where('port_of_discharge', $r->port_of_discharge);
        if ($r->status) $q->where('status', $r->status);
        if ($r->upcoming) $q->where('etd', '>=', now());
        return response()->json(['data' => $q->orderBy('etd')->paginate($r->per_page ?? 25)]);
    }

    public function schedulesStore(Request $r): JsonResponse
    {
        $v = $r->validate([
            'vessel_id' => 'required|uuid|exists:vessels,id',
            'voyage_number' => 'required|string|max:50',
            'service_route' => 'nullable|string|max:100',
            'port_of_loading' => 'required|string|max:5',
            'port_of_loading_name' => 'nullable|string|max:200',
            'port_of_discharge' => 'required|string|max:5',
            'port_of_discharge_name' => 'nullable|string|max:200',
            'etd' => 'required|date',
            'eta' => 'required|date|after:etd',
            'cut_off_date' => 'nullable|date|before:etd',
            'transit_days' => 'nullable|integer',
            'port_calls' => 'nullable|array',
        ]);
        $v['account_id'] = $r->user()->account_id;
        return response()->json(['data' => VesselSchedule::create($v), 'message' => 'تم إضافة جدول الرحلة'], 201);
    }

    public function schedulesShow(string $id): JsonResponse
    {
        return response()->json(['data' => VesselSchedule::with(['vessel', 'containers'])->findOrFail($id)]);
    }

    public function schedulesUpdate(Request $r, string $id): JsonResponse
    {
        $s = VesselSchedule::findOrFail($id);
        $s->update($r->only(['voyage_number', 'etd', 'eta', 'atd', 'ata', 'cut_off_date', 'transit_days', 'status', 'port_calls']));
        return response()->json(['data' => $s, 'message' => 'تم تحديث الجدول']);
    }

    // ══════════════════════════════════════════════════════════════
    // CONTAINERS
    // ══════════════════════════════════════════════════════════════

    public function index(Request $r): JsonResponse
    {
        $q = Container::where('account_id', $r->user()->account_id)->with('vesselSchedule.vessel:id,vessel_name');
        if ($r->status) $q->where('status', $r->status);
        if ($r->size) $q->where('size', $r->size);
        if ($r->type) $q->where('type', $r->type);
        if ($r->vessel_schedule_id) $q->where('vessel_schedule_id', $r->vessel_schedule_id);
        if ($r->search) $q->where('container_number', 'like', "%{$r->search}%");
        return response()->json(['data' => $q->orderByDesc('created_at')->paginate($r->per_page ?? 25)]);
    }

    public function store(Request $r): JsonResponse
    {
        $v = $r->validate([
            'container_number' => 'required|string|max:15',
            'size' => 'required|in:20ft,40ft,40ft_hc,45ft',
            'type' => 'required|in:dry,reefer,open_top,flat_rack,tank,special',
            'vessel_schedule_id' => 'nullable|uuid|exists:vessel_schedules,id',
            'seal_number' => 'nullable|string|max:50',
            'tare_weight' => 'nullable|numeric',
            'max_payload' => 'nullable|numeric',
            'origin_branch_id' => 'nullable|uuid|exists:branches,id',
            'destination_branch_id' => 'nullable|uuid|exists:branches,id',
            'temperature_min' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
        ]);
        $v['account_id'] = $r->user()->account_id;
        return response()->json(['data' => Container::create($v), 'message' => 'تم إضافة الحاوية'], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => Container::with(['vesselSchedule.vessel', 'shipments', 'originBranch', 'destinationBranch'])->findOrFail($id)]);
    }

    public function update(Request $r, string $id): JsonResponse
    {
        $c = Container::findOrFail($id);
        $c->update($r->only(['seal_number', 'current_weight', 'location', 'status', 'temperature_min', 'temperature_max', 'vessel_schedule_id']));
        return response()->json(['data' => $c, 'message' => 'تم تحديث الحاوية']);
    }

    public function destroy(string $id): JsonResponse
    {
        Container::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الحاوية']);
    }

    // ── Load/Unload Shipments ────────────────────────────────────
    public function loadShipment(Request $r, string $id): JsonResponse
    {
        $r->validate([
            'shipment_id' => 'required|uuid|exists:shipments,id',
            'packages_count' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric',
            'volume_cbm' => 'nullable|numeric',
        ]);

        ContainerShipment::create([
            'container_id' => $id,
            'shipment_id' => $r->shipment_id,
            'packages_count' => $r->packages_count ?? 1,
            'weight' => $r->weight,
            'volume_cbm' => $r->volume_cbm,
            'loaded_at' => now(),
        ]);

        // Update container weight
        $c = Container::findOrFail($id);
        if ($r->weight) $c->increment('current_weight', $r->weight);
        $c->update(['status' => 'loading']);

        return response()->json(['message' => 'تم تحميل الشحنة على الحاوية']);
    }

    public function unloadShipment(string $id, string $shipmentId): JsonResponse
    {
        $cs = ContainerShipment::where('container_id', $id)->where('shipment_id', $shipmentId)->firstOrFail();
        $cs->update(['unloaded_at' => now()]);

        $c = Container::findOrFail($id);
        if ($cs->weight) $c->decrement('current_weight', $cs->weight);

        return response()->json(['message' => 'تم تفريغ الشحنة من الحاوية']);
    }

    public function stats(Request $r): JsonResponse
    {
        $aid = $r->user()->account_id;
        return response()->json(['data' => [
            'total_containers' => Container::where('account_id', $aid)->count(),
            'by_status' => Container::where('account_id', $aid)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
            'by_size' => Container::where('account_id', $aid)->selectRaw('size, count(*) as count')->groupBy('size')->pluck('count', 'size'),
            'total_vessels' => Vessel::where('account_id', $aid)->count(),
            'upcoming_voyages' => VesselSchedule::where('account_id', $aid)->where('etd', '>=', now())->count(),
        ]]);
    }
}
