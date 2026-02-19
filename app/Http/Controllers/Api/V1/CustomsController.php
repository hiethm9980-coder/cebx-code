<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomsDeclaration;
use App\Models\CustomsDocument;
use App\Models\CustomsBroker;
use App\Models\ShipmentItem;
use App\Models\HsCode;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomsController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // CUSTOMS DECLARATIONS
    // ══════════════════════════════════════════════════════════════

    public function index(Request $r): JsonResponse
    {
        $q = CustomsDeclaration::where('account_id', $r->user()->account_id)->with(['shipment:id,tracking_number,status', 'broker:id,name']);
        if ($r->customs_status) $q->where('customs_status', $r->customs_status);
        if ($r->declaration_type) $q->where('declaration_type', $r->declaration_type);
        if ($r->shipment_id) $q->where('shipment_id', $r->shipment_id);
        if ($r->broker_id) $q->where('broker_id', $r->broker_id);
        if ($r->search) $q->where(fn($q) => $q->where('declaration_number', 'like', "%{$r->search}%"));
        return response()->json(['data' => $q->orderByDesc('created_at')->paginate($r->per_page ?? 25)]);
    }

    public function store(Request $r): JsonResponse
    {
        $v = $r->validate([
            'shipment_id' => 'required|uuid|exists:shipments,id',
            'broker_id' => 'nullable|uuid|exists:customs_brokers,id',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'declaration_type' => 'required|in:export,import,transit,re_export',
            'customs_office' => 'nullable|string|max:200',
            'origin_country' => 'required|string|size:2',
            'destination_country' => 'required|string|size:2',
            'incoterm_code' => 'nullable|string|max:3',
            'declared_value' => 'required|numeric|min:0',
            'declared_currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.hs_code' => 'nullable|string|max:12',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.weight' => 'nullable|numeric',
            'items.*.unit_value' => 'required_with:items|numeric|min:0',
        ]);

        $v['account_id'] = $r->user()->account_id;
        $v['customs_status'] = 'draft';
        $decl = CustomsDeclaration::create($v);

        // Create items with HS code lookup
        if ($r->items) {
            foreach ($r->items as $item) {
                $item['shipment_id'] = $v['shipment_id'];
                $item['declaration_id'] = $decl->id;
                $item['total_value'] = ($item['quantity'] ?? 1) * ($item['unit_value'] ?? 0);
                ShipmentItem::create($item);
            }
        }

        // Auto-calculate duties based on HS codes
        $this->calculateDuties($decl);

        return response()->json(['data' => $decl->load(['items', 'documents']), 'message' => 'تم إنشاء البيان الجمركي'], 201);
    }

    public function show(string $id): JsonResponse
    {
        $d = CustomsDeclaration::with(['shipment', 'broker', 'documents', 'items', 'branch', 'inspector'])->findOrFail($id);
        return response()->json(['data' => $d]);
    }

    public function update(Request $r, string $id): JsonResponse
    {
        $d = CustomsDeclaration::findOrFail($id);
        if (!in_array($d->customs_status, ['draft', 'documents_pending'])) {
            return response()->json(['message' => 'لا يمكن التعديل في هذه المرحلة'], 422);
        }
        $d->update($r->only(['broker_id', 'branch_id', 'customs_office', 'incoterm_code', 'declared_value', 'declared_currency', 'notes']));
        $this->calculateDuties($d);
        return response()->json(['data' => $d, 'message' => 'تم تحديث البيان']);
    }

    // ── Status Transitions ───────────────────────────────────────
    public function updateStatus(Request $r, string $id): JsonResponse
    {
        $r->validate(['status' => 'required|string', 'notes' => 'nullable|string']);
        $d = CustomsDeclaration::findOrFail($id);

        if (!$d->canTransitionTo($r->status)) {
            return response()->json(['message' => "لا يمكن الانتقال من {$d->customs_status} إلى {$r->status}"], 422);
        }

        $oldStatus = $d->customs_status;
        $updates = ['customs_status' => $r->status];

        // Auto-set timestamps
        match ($r->status) {
            'submitted' => $updates['submitted_at'] = now(),
            'cleared' => $updates['cleared_at'] = now(),
            'duty_paid' => array_merge($updates, ['duty_paid_at' => now(), 'duty_payment_ref' => $r->payment_ref ?? null]),
            'inspecting' => array_merge($updates, ['inspection_flag' => true, 'inspection_date' => now(), 'inspector_user_id' => $r->user()->id]),
            default => null,
        };

        if ($r->inspection_result) $updates['inspection_result'] = $r->inspection_result;
        if ($r->inspection_notes) $updates['inspection_notes'] = $r->inspection_notes;

        $d->update($updates);

        AuditLog::create([
            'account_id' => $d->account_id, 'user_id' => $r->user()->id,
            'action' => 'customs_status_change', 'entity_type' => 'customs_declaration', 'entity_id' => $id,
            'old_values' => ['status' => $oldStatus], 'new_values' => ['status' => $r->status],
            'description' => "تغيير حالة الجمارك: {$oldStatus} → {$r->status}",
        ]);

        return response()->json(['data' => $d, 'message' => 'تم تحديث حالة البيان الجمركي']);
    }

    // ── Document Upload ──────────────────────────────────────────
    public function uploadDocument(Request $r, string $id): JsonResponse
    {
        $r->validate([
            'document_type' => 'required|string',
            'document_name' => 'required|string|max:200',
            'document_number' => 'nullable|string|max:100',
            'file' => 'required|file|max:10240',
            'is_required' => 'nullable|boolean',
        ]);

        $decl = CustomsDeclaration::findOrFail($id);
        $file = $r->file('file');
        $path = $file->store("customs/{$id}", 'public');

        $doc = CustomsDocument::create([
            'declaration_id' => $id,
            'shipment_id' => $decl->shipment_id,
            'document_type' => $r->document_type,
            'document_name' => $r->document_name,
            'document_number' => $r->document_number,
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $r->user()->id,
            'is_required' => $r->is_required ?? true,
        ]);

        return response()->json(['data' => $doc, 'message' => 'تم رفع المستند'], 201);
    }

    public function verifyDocument(Request $r, string $id, string $docId): JsonResponse
    {
        $doc = CustomsDocument::where('declaration_id', $id)->findOrFail($docId);
        $action = $r->input('action', 'approve');

        if ($action === 'approve') {
            $doc->update(['is_verified' => true, 'verified_by' => $r->user()->id, 'verified_at' => now(), 'rejection_reason' => null]);
        } else {
            $doc->update(['is_verified' => false, 'rejection_reason' => $r->rejection_reason]);
        }

        return response()->json(['data' => $doc, 'message' => $action === 'approve' ? 'تم التحقق من المستند' : 'تم رفض المستند']);
    }

    // ══════════════════════════════════════════════════════════════
    // CUSTOMS BROKERS
    // ══════════════════════════════════════════════════════════════

    public function brokersIndex(Request $r): JsonResponse
    {
        $q = CustomsBroker::where('account_id', $r->user()->account_id);
        if ($r->country) $q->where('country', $r->country);
        if ($r->status) $q->where('status', $r->status);
        if ($r->search) $q->where(fn($q) => $q->where('name', 'like', "%{$r->search}%")->orWhere('license_number', 'like', "%{$r->search}%"));
        return response()->json(['data' => $q->orderBy('rating', 'desc')->paginate($r->per_page ?? 25)]);
    }

    public function brokersStore(Request $r): JsonResponse
    {
        $v = $r->validate([
            'name' => 'required|string|max:200',
            'license_number' => 'required|string|max:100',
            'country' => 'required|string|size:2',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'company_name' => 'nullable|string|max:200',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'fixed_fee' => 'nullable|numeric|min:0',
            'specializations' => 'nullable|array',
        ]);
        $v['account_id'] = $r->user()->account_id;
        return response()->json(['data' => CustomsBroker::create($v), 'message' => 'تم إضافة المخلص الجمركي'], 201);
    }

    public function brokersShow(string $id): JsonResponse
    {
        return response()->json(['data' => CustomsBroker::with('declarations')->findOrFail($id)]);
    }

    public function brokersUpdate(Request $r, string $id): JsonResponse
    {
        $b = CustomsBroker::findOrFail($id);
        $b->update($r->only(['name', 'license_number', 'country', 'city', 'phone', 'email', 'company_name', 'commission_rate', 'fixed_fee', 'status', 'specializations']));
        return response()->json(['data' => $b, 'message' => 'تم تحديث المخلص']);
    }

    // ── Stats ────────────────────────────────────────────────────
    public function stats(Request $r): JsonResponse
    {
        $aid = $r->user()->account_id;
        return response()->json(['data' => [
            'total_declarations' => CustomsDeclaration::where('account_id', $aid)->count(),
            'by_status' => CustomsDeclaration::where('account_id', $aid)->selectRaw('customs_status, count(*) as count')->groupBy('customs_status')->pluck('count', 'customs_status'),
            'pending_clearance' => CustomsDeclaration::where('account_id', $aid)->whereNotIn('customs_status', ['cleared', 'cancelled', 'rejected'])->count(),
            'cleared_this_month' => CustomsDeclaration::where('account_id', $aid)->where('customs_status', 'cleared')->whereMonth('cleared_at', now()->month)->count(),
            'total_duties' => CustomsDeclaration::where('account_id', $aid)->where('customs_status', 'cleared')->sum('total_customs_charges'),
            'active_brokers' => CustomsBroker::where('account_id', $aid)->where('status', 'active')->count(),
        ]]);
    }

    // ── Duty Calculator ──────────────────────────────────────────
    private function calculateDuties(CustomsDeclaration $decl): void
    {
        $items = ShipmentItem::where('declaration_id', $decl->id)->get();
        $totalDuty = 0; $totalVat = 0; $totalExcise = 0;

        foreach ($items as $item) {
            if (!$item->hs_code) continue;
            $hs = HsCode::where('code', $item->hs_code)->where(fn($q) => $q->where('country', $decl->destination_country)->orWhere('country', '*'))->first();
            if (!$hs) continue;

            $duties = $hs->calculateDuty($item->total_value);
            $totalDuty += $duties['duty'];
            $totalVat += $duties['vat'];
            $totalExcise += $duties['excise'];
        }

        // Broker fee
        $brokerFee = 0;
        if ($decl->broker_id) {
            $broker = CustomsBroker::find($decl->broker_id);
            if ($broker) {
                $brokerFee = $broker->fixed_fee + ($decl->declared_value * $broker->commission_rate / 100);
            }
        }

        $decl->update([
            'duty_amount' => $totalDuty,
            'vat_amount' => $totalVat,
            'excise_amount' => $totalExcise,
            'broker_fee' => round($brokerFee, 2),
            'total_customs_charges' => $totalDuty + $totalVat + $totalExcise + $brokerFee,
        ]);
    }
}
