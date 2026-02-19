<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VesselController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $accountId = $request->user()->account_id;
        $query = Vessel::where('account_id', $accountId);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('imo_number', 'ilike', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        return response()->json($query->withCount('containers')->latest()->paginate($request->per_page ?? 25));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'imo_number' => 'nullable|string|max:50',
            'flag' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'capacity_teu' => 'nullable|integer',
        ]);

        $data['account_id'] = $request->user()->account_id;
        $vessel = Vessel::create($data);

        $this->audit->log('vessel.created', $vessel, $request);
        return response()->json(['data' => $vessel], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $vessel = Vessel::where('account_id', $request->user()->account_id)
            ->with(['schedules', 'containers'])
            ->findOrFail($id);
        return response()->json(['data' => $vessel]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $vessel = Vessel::where('account_id', $request->user()->account_id)->findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'imo_number' => 'nullable|string|max:50',
            'flag' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'capacity_teu' => 'nullable|integer',
            'status' => 'sometimes|in:active,inactive,maintenance',
        ]);

        $vessel->update($data);
        $this->audit->log('vessel.updated', $vessel, $request);
        return response()->json(['data' => $vessel]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $vessel = Vessel::where('account_id', $request->user()->account_id)->findOrFail($id);
        $vessel->delete();
        $this->audit->log('vessel.deleted', $vessel, $request);
        return response()->json(null, 204);
    }
}
