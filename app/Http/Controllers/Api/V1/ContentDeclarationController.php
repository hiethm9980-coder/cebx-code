<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContentDeclaration;
use App\Models\Shipment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * CBEX GROUP — Content Declaration Controller
 *
 * Manages customs content declarations for shipments.
 */
class ContentDeclarationController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = ContentDeclaration::where('account_id', $request->user()->account_id)
            ->with('shipment:id,tracking_number,status');

        if ($request->filled('shipment_id')) $query->where('shipment_id', $request->shipment_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        return response()->json(['data' => $query->orderBy('created_at', 'desc')->paginate(20)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipment_id' => 'required|uuid|exists:shipments,id',
            'declaration_type' => 'required|in:export,import,transit',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:300',
            'items.*.hs_code' => 'nullable|string|max:20',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.weight_kg' => 'required|numeric|min:0.01',
            'items.*.value' => 'required|numeric|min:0',
            'items.*.country_of_origin' => 'nullable|string|size:2',
            'total_value' => 'required|numeric|min:0',
            'currency' => 'string|size:3',
            'purpose' => 'nullable|in:commercial,personal,gift,sample,return',
            'notes' => 'nullable|string|max:1000',
        ]);

        $declaration = ContentDeclaration::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $request->user()->account_id,
            'shipment_id' => $data['shipment_id'],
            'declaration_number' => 'DEC-' . strtoupper(Str::random(8)),
            'declaration_type' => $data['declaration_type'],
            'items' => $data['items'],
            'total_items' => count($data['items']),
            'total_weight' => array_sum(array_column($data['items'], 'weight_kg')),
            'total_value' => $data['total_value'],
            'currency' => $data['currency'] ?? 'SAR',
            'purpose' => $data['purpose'] ?? 'commercial',
            'notes' => $data['notes'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        $this->audit->log('declaration.created', $declaration);

        return response()->json(['data' => $declaration, 'message' => 'تم إنشاء البيان الجمركي'], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $declaration = ContentDeclaration::where('account_id', $request->user()->account_id)
            ->with('shipment:id,tracking_number,status,origin_country,destination_country')
            ->findOrFail($id);

        return response()->json(['data' => $declaration]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $declaration = ContentDeclaration::where('account_id', $request->user()->account_id)->findOrFail($id);

        if ($declaration->status === 'submitted') {
            return response()->json(['message' => 'لا يمكن تعديل بيان تم تقديمه'], 422);
        }

        $data = $request->validate([
            'items' => 'array|min:1',
            'items.*.description' => 'required|string',
            'items.*.hs_code' => 'nullable|string',
            'items.*.quantity' => 'required|integer',
            'items.*.weight_kg' => 'required|numeric',
            'items.*.value' => 'required|numeric',
            'items.*.country_of_origin' => 'nullable|string|size:2',
            'total_value' => 'numeric',
            'purpose' => 'in:commercial,personal,gift,sample,return',
            'notes' => 'nullable|string',
        ]);

        if (isset($data['items'])) {
            $data['total_items'] = count($data['items']);
            $data['total_weight'] = array_sum(array_column($data['items'], 'weight_kg'));
        }

        $declaration->update($data);

        return response()->json(['data' => $declaration->fresh()]);
    }

    /**
     * Submit declaration for customs review
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $declaration = ContentDeclaration::where('account_id', $request->user()->account_id)->findOrFail($id);

        if ($declaration->status !== 'draft') {
            return response()->json(['message' => 'البيان غير قابل للتقديم في حالته الحالية'], 422);
        }

        $declaration->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => $request->user()->id,
        ]);

        $this->audit->log('declaration.submitted', $declaration);

        return response()->json(['data' => $declaration->fresh(), 'message' => 'تم تقديم البيان الجمركي']);
    }

    /**
     * Approve/reject declaration (admin)
     */
    public function review(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        $declaration = ContentDeclaration::where('account_id', $request->user()->account_id)->findOrFail($id);

        $declaration->update([
            'status' => $data['action'] === 'approve' ? 'approved' : 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log("declaration.{$data['action']}", $declaration);

        return response()->json(['data' => $declaration->fresh()]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $declaration = ContentDeclaration::where('account_id', $request->user()->account_id)->findOrFail($id);

        if ($declaration->status !== 'draft') {
            return response()->json(['message' => 'لا يمكن حذف بيان تم تقديمه'], 422);
        }

        $declaration->delete();
        return response()->json(['message' => 'تم حذف البيان']);
    }
}
