<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Branch;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * CBEX GROUP — Company Management Controller
 *
 * Manages companies within a tenant account.
 * One account can have multiple companies (holding structure).
 */
class CompanyController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    /**
     * List companies in account
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::where('account_id', $request->user()->account_id);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('country')) $query->where('country', $request->country);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('legal_name', 'ilike', "%{$request->search}%");
            });
        }

        $companies = $query->withCount('branches')
            ->orderBy($request->get('sort', 'name'))
            ->paginate($request->get('per_page', 20));

        return response()->json(['data' => $companies]);
    }

    /**
     * Create a new company
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'legal_name' => 'nullable|string|max:300',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'country' => 'required|string|size:2',
            'base_currency' => 'string|size:3',
            'timezone' => 'string|max:50',
            'industry' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:300',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        $data['id'] = Str::uuid()->toString();
        $data['account_id'] = $request->user()->account_id;
        $data['status'] = 'active';

        $company = Company::create($data);

        $this->audit->log('company.created', $company, ['name' => $company->name]);

        return response()->json(['data' => $company, 'message' => 'تم إنشاء الشركة بنجاح'], 201);
    }

    /**
     * Show company details with branches
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $company = Company::where('account_id', $request->user()->account_id)
            ->with(['branches' => fn($q) => $q->where('status', 'active')])
            ->withCount(['branches'])
            ->findOrFail($id);

        return response()->json(['data' => $company]);
    }

    /**
     * Update company
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $company = Company::where('account_id', $request->user()->account_id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'string|max:200',
            'legal_name' => 'nullable|string|max:300',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'country' => 'string|size:2',
            'base_currency' => 'string|size:3',
            'timezone' => 'string|max:50',
            'industry' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:300',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'status' => 'in:active,suspended,inactive',
        ]);

        $company->update($data);
        $this->audit->log('company.updated', $company);

        return response()->json(['data' => $company->fresh(), 'message' => 'تم تحديث الشركة']);
    }

    /**
     * Delete (soft delete) company
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $company = Company::where('account_id', $request->user()->account_id)->findOrFail($id);

        if ($company->branches()->where('status', 'active')->exists()) {
            return response()->json(['message' => 'لا يمكن حذف شركة لديها فروع نشطة'], 422);
        }

        $company->delete();
        $this->audit->log('company.deleted', $company);

        return response()->json(['message' => 'تم حذف الشركة']);
    }

    /**
     * Get company stats
     */
    public function stats(Request $request, string $id): JsonResponse
    {
        $company = Company::where('account_id', $request->user()->account_id)->findOrFail($id);

        return response()->json(['data' => [
            'total_branches' => $company->branches()->count(),
            'active_branches' => $company->branches()->where('status', 'active')->count(),
            'total_staff' => $company->branches()->withCount('staff')->get()->sum('staff_count'),
            'countries' => $company->branches()->distinct('country')->pluck('country'),
            'branch_types' => $company->branches()
                ->selectRaw('branch_type, count(*) as count')
                ->groupBy('branch_type')->pluck('count', 'branch_type'),
        ]]);
    }

    /**
     * List branches for a company
     */
    public function branches(Request $request, string $id): JsonResponse
    {
        $company = Company::where('account_id', $request->user()->account_id)->findOrFail($id);

        $branches = $company->branches()
            ->withCount('staff')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn($q) => $q->where('branch_type', $request->type))
            ->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return response()->json(['data' => $branches]);
    }
}
