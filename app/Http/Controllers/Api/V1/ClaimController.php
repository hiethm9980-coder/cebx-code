<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClaimDocument;
use App\Models\ClaimHistory;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * CLM Module — Claims & Insurance
 * المطالبات والتأمين
 */
class ClaimController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // CLAIMS CRUD
    // ══════════════════════════════════════════════════════════════

    public function index(Request $r): JsonResponse
    {
        $q = Claim::where('account_id', $r->user()->account_id)
            ->with(['shipment:id,tracking_number,status', 'filer:id,name', 'assignee:id,name']);

        if ($r->status) $q->where('status', $r->status);
        if ($r->claim_type) $q->where('claim_type', $r->claim_type);
        if ($r->shipment_id) $q->where('shipment_id', $r->shipment_id);
        if ($r->assigned_to) $q->where('assigned_to', $r->assigned_to);
        if ($r->search) $q->where(fn($q2) => $q2->where('claim_number', 'like', "%{$r->search}%")
            ->orWhere('description', 'like', "%{$r->search}%"));
        if ($r->date_from) $q->whereDate('created_at', '>=', $r->date_from);
        if ($r->date_to) $q->whereDate('created_at', '<=', $r->date_to);
        if ($r->overdue) $q->where('sla_deadline', '<', now())->whereNotIn('status', ['settled', 'closed', 'rejected']);

        $sort = $r->sort ?? '-created_at';
        $dir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col = ltrim($sort, '-');
        $q->orderBy($col, $dir);

        return response()->json(['data' => $q->paginate($r->per_page ?? 25)]);
    }

    public function store(Request $r): JsonResponse
    {
        $v = $r->validate([
            'shipment_id'      => 'required|uuid|exists:shipments,id',
            'claim_type'       => 'required|in:damage,loss,shortage,delay,wrong_delivery,theft,water_damage,temperature_deviation,other',
            'description'      => 'required|string|min:20|max:5000',
            'claimed_amount'   => 'required|numeric|min:0.01',
            'claimed_currency' => 'nullable|string|size:3',
            'incident_date'    => 'required|date|before_or_equal:today',
            'incident_location'=> 'nullable|string|max:300',
        ]);

        $shipment = Shipment::findOrFail($r->shipment_id);

        $v['account_id']   = $r->user()->account_id;
        $v['claim_number'] = Claim::generateNumber();
        $v['status']       = 'draft';
        $v['filed_by']     = $r->user()->id;
        // SLA: 14 business days
        $v['sla_deadline']  = now()->addWeekdays(14)->toDateString();

        $claim = Claim::create($v);

        // Log history
        ClaimHistory::create([
            'claim_id' => $claim->id,
            'from_status' => 'new',
            'to_status' => 'draft',
            'changed_by' => $r->user()->id,
            'notes' => 'تم إنشاء المطالبة',
        ]);

        return response()->json(['data' => $claim->load(['shipment', 'filer']), 'message' => 'تم إنشاء المطالبة بنجاح'], 201);
    }

    public function show(string $id): JsonResponse
    {
        $claim = Claim::with([
            'shipment:id,tracking_number,status,carrier_name,total_charge',
            'filer:id,name,email',
            'assignee:id,name,email',
            'approver:id,name',
            'documents',
            'history.user:id,name',
        ])->findOrFail($id);

        return response()->json(['data' => $claim]);
    }

    public function update(Request $r, string $id): JsonResponse
    {
        $claim = Claim::findOrFail($id);

        if (!in_array($claim->status, ['draft', 'submitted'])) {
            return response()->json(['message' => 'لا يمكن تعديل المطالبة في هذه المرحلة'], 422);
        }

        $claim->update($r->only([
            'description', 'claimed_amount', 'claimed_currency',
            'incident_date', 'incident_location',
        ]));

        return response()->json(['data' => $claim, 'message' => 'تم تحديث المطالبة']);
    }

    // ══════════════════════════════════════════════════════════════
    // STATUS TRANSITIONS
    // ══════════════════════════════════════════════════════════════

    public function submit(Request $r, string $id): JsonResponse
    {
        return $this->transition($r, $id, 'submitted', 'تم تقديم المطالبة');
    }

    public function assign(Request $r, string $id): JsonResponse
    {
        $r->validate(['assigned_to' => 'required|uuid|exists:users,id']);
        $claim = Claim::findOrFail($id);
        $claim->update(['assigned_to' => $r->assigned_to, 'status' => 'under_review']);

        ClaimHistory::create([
            'claim_id' => $id, 'from_status' => $claim->status,
            'to_status' => 'under_review', 'changed_by' => $r->user()->id,
            'notes' => 'تم تعيين المطالبة لمعالج',
        ]);

        return response()->json(['data' => $claim, 'message' => 'تم تعيين المطالبة']);
    }

    public function investigate(Request $r, string $id): JsonResponse
    {
        return $this->transition($r, $id, 'investigation', 'بدأ التحقيق');
    }

    public function assess(Request $r, string $id): JsonResponse
    {
        return $this->transition($r, $id, 'assessment', 'جاري التقييم');
    }

    public function approve(Request $r, string $id): JsonResponse
    {
        $r->validate([
            'approved_amount' => 'required|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $claim = Claim::findOrFail($id);
        $status = $r->approved_amount < $claim->claimed_amount ? 'partially_approved' : 'approved';

        $claim->update([
            'status' => $status,
            'approved_amount' => $r->approved_amount,
            'approved_by' => $r->user()->id,
            'resolved_at' => now(),
            'resolution_notes' => $r->notes,
        ]);

        ClaimHistory::create([
            'claim_id' => $id, 'from_status' => $claim->getOriginal('status'),
            'to_status' => $status, 'changed_by' => $r->user()->id,
            'notes' => "تمت الموافقة — المبلغ: {$r->approved_amount}",
        ]);

        return response()->json(['data' => $claim, 'message' => 'تمت الموافقة على المطالبة']);
    }

    public function reject(Request $r, string $id): JsonResponse
    {
        $r->validate(['reason' => 'required|string|min:10']);
        $claim = Claim::findOrFail($id);
        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => $r->reason,
            'resolved_at' => now(),
        ]);

        ClaimHistory::create([
            'claim_id' => $id, 'from_status' => $claim->getOriginal('status'),
            'to_status' => 'rejected', 'changed_by' => $r->user()->id,
            'notes' => "تم الرفض: {$r->reason}",
        ]);

        return response()->json(['data' => $claim, 'message' => 'تم رفض المطالبة']);
    }

    public function settle(Request $r, string $id): JsonResponse
    {
        $r->validate([
            'settled_amount'     => 'required|numeric|min:0',
            'settlement_ref'     => 'nullable|string|max:100',
            'settlement_currency'=> 'nullable|string|size:3',
        ]);

        $claim = Claim::findOrFail($id);
        $claim->update([
            'status' => 'settled',
            'settled_amount' => $r->settled_amount,
            'settlement_ref' => $r->settlement_ref,
            'settlement_currency' => $r->settlement_currency ?? $claim->claimed_currency,
            'settled_at' => now(),
        ]);

        ClaimHistory::create([
            'claim_id' => $id, 'from_status' => $claim->getOriginal('status'),
            'to_status' => 'settled', 'changed_by' => $r->user()->id,
            'notes' => "تمت التسوية — المبلغ: {$r->settled_amount}",
        ]);

        return response()->json(['data' => $claim, 'message' => 'تمت تسوية المطالبة']);
    }

    public function close(Request $r, string $id): JsonResponse
    {
        return $this->transition($r, $id, 'closed', 'تم إغلاق المطالبة');
    }

    public function appeal(Request $r, string $id): JsonResponse
    {
        $r->validate(['reason' => 'required|string|min:10']);
        $claim = Claim::findOrFail($id);
        if (!in_array($claim->status, ['rejected', 'partially_approved'])) {
            return response()->json(['message' => 'لا يمكن الاعتراض في هذه المرحلة'], 422);
        }
        $claim->update(['status' => 'appealed']);
        ClaimHistory::create([
            'claim_id' => $id, 'from_status' => $claim->getOriginal('status'),
            'to_status' => 'appealed', 'changed_by' => $r->user()->id,
            'notes' => "اعتراض: {$r->reason}",
        ]);
        return response()->json(['data' => $claim, 'message' => 'تم تقديم الاعتراض']);
    }

    // ══════════════════════════════════════════════════════════════
    // DOCUMENTS
    // ══════════════════════════════════════════════════════════════

    public function uploadDocument(Request $r, string $id): JsonResponse
    {
        $r->validate([
            'document_type' => 'required|in:photo,video,invoice,receipt,report,correspondence,other',
            'title'         => 'required|string|max:200',
            'file'          => 'required|file|max:20480',
            'notes'         => 'nullable|string',
        ]);

        $file = $r->file('file');
        $path = $file->store("claims/{$id}", 'public');

        $doc = ClaimDocument::create([
            'claim_id' => $id,
            'document_type' => $r->document_type,
            'title' => $r->title,
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $r->user()->id,
            'notes' => $r->notes,
        ]);

        return response()->json(['data' => $doc, 'message' => 'تم رفع المستند'], 201);
    }

    public function deleteDocument(string $id, string $docId): JsonResponse
    {
        ClaimDocument::where('claim_id', $id)->findOrFail($docId)->delete();
        return response()->json(['message' => 'تم حذف المستند']);
    }

    // ══════════════════════════════════════════════════════════════
    // HISTORY & STATS
    // ══════════════════════════════════════════════════════════════

    public function history(string $id): JsonResponse
    {
        return response()->json(['data' => ClaimHistory::where('claim_id', $id)->with('user:id,name')->orderByDesc('created_at')->get()]);
    }

    public function stats(Request $r): JsonResponse
    {
        $aid = $r->user()->account_id;
        $q = Claim::where('account_id', $aid);

        return response()->json(['data' => [
            'total'             => (clone $q)->count(),
            'by_status'         => (clone $q)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
            'by_type'           => (clone $q)->selectRaw('claim_type, count(*) as count')->groupBy('claim_type')->pluck('count', 'claim_type'),
            'open'              => (clone $q)->whereNotIn('status', ['settled', 'closed', 'rejected'])->count(),
            'overdue'           => (clone $q)->where('sla_deadline', '<', now())->whereNotIn('status', ['settled', 'closed', 'rejected'])->count(),
            'total_claimed'     => (clone $q)->sum('claimed_amount'),
            'total_approved'    => (clone $q)->whereNotNull('approved_amount')->sum('approved_amount'),
            'total_settled'     => (clone $q)->where('status', 'settled')->sum('settled_amount'),
            'avg_resolution_days' => round((clone $q)->whereNotNull('resolved_at')->selectRaw('AVG(DATEDIFF(resolved_at, created_at)) as avg')->value('avg') ?? 0),
            'this_month'        => (clone $q)->whereMonth('created_at', now()->month)->count(),
        ]]);
    }

    // ── Helper ───────────────────────────────────────────────────
    private function transition(Request $r, string $id, string $toStatus, string $message): JsonResponse
    {
        $claim = Claim::findOrFail($id);
        $from = $claim->status;
        $claim->update(['status' => $toStatus]);

        ClaimHistory::create([
            'claim_id' => $id, 'from_status' => $from,
            'to_status' => $toStatus, 'changed_by' => $r->user()->id,
            'notes' => $r->notes ?? $message,
        ]);

        return response()->json(['data' => $claim, 'message' => $message]);
    }
}
