<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Branch;
use App\Models\BranchStaff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // COMPANIES
    // ══════════════════════════════════════════════════════════════

    public function companiesIndex(Request $r): JsonResponse
    {
        $q = Company::where('account_id', $r->user()->account_id);
        if ($r->status) $q->where('status', $r->status);
        if ($r->search) $q->where(fn($q) => $q->where('name', 'like', "%{$r->search}%")->orWhere('legal_name', 'like', "%{$r->search}%"));
        return response()->json(['data' => $q->orderBy('name')->paginate($r->per_page ?? 25)]);
    }

    public function companiesStore(Request $r): JsonResponse
    {
        $v = $r->validate([
            'name' => 'required|string|max:200',
            'legal_name' => 'nullable|string|max:300',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'country' => 'required|string|size:2',
            'base_currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
        ]);
        $v['account_id'] = $r->user()->account_id;
        $company = Company::create($v);
        return response()->json(['data' => $company, 'message' => 'تم إنشاء الشركة بنجاح'], 201);
    }

    public function companiesShow(string $id): JsonResponse
    {
        $c = Company::with('branches')->findOrFail($id);
        return response()->json(['data' => $c]);
    }

    public function companiesUpdate(Request $r, string $id): JsonResponse
    {
        $c = Company::findOrFail($id);
        $c->update($r->only(['name', 'legal_name', 'registration_number', 'tax_id', 'country', 'base_currency', 'timezone', 'industry', 'phone', 'email', 'address', 'website', 'status']));
        return response()->json(['data' => $c, 'message' => 'تم تحديث الشركة']);
    }

    public function companiesDestroy(string $id): JsonResponse
    {
        Company::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الشركة']);
    }

    // ══════════════════════════════════════════════════════════════
    // BRANCHES
    // ══════════════════════════════════════════════════════════════

    public function index(Request $r): JsonResponse
    {
        $q = Branch::where('account_id', $r->user()->account_id)->with('company:id,name');
        if ($r->company_id) $q->where('company_id', $r->company_id);
        if ($r->branch_type) $q->where('branch_type', $r->branch_type);
        if ($r->country) $q->where('country', $r->country);
        if ($r->status) $q->where('status', $r->status);
        if ($r->search) $q->where(fn($q) => $q->where('name', 'like', "%{$r->search}%")->orWhere('code', 'like', "%{$r->search}%")->orWhere('city', 'like', "%{$r->search}%"));
        return response()->json(['data' => $q->orderBy('name')->paginate($r->per_page ?? 25)]);
    }

    public function store(Request $r): JsonResponse
    {
        $v = $r->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'name' => 'required|string|max:200',
            'code' => 'required|string|max:20|unique:branches,code',
            'country' => 'required|string|size:2',
            'city' => 'required|string|max:100',
            'branch_type' => 'required|in:headquarters,hub,port,airport,office,warehouse,customs_office',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'manager_name' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capabilities' => 'nullable|array',
            'operating_hours' => 'nullable|array',
        ]);
        $v['account_id'] = $r->user()->account_id;
        $branch = Branch::create($v);
        return response()->json(['data' => $branch, 'message' => 'تم إنشاء الفرع بنجاح'], 201);
    }

    public function show(string $id): JsonResponse
    {
        $b = Branch::with(['company', 'staff.user', 'drivers'])->findOrFail($id);
        return response()->json(['data' => $b]);
    }

    public function update(Request $r, string $id): JsonResponse
    {
        $b = Branch::findOrFail($id);
        $b->update($r->only(['name', 'city', 'address', 'branch_type', 'phone', 'email', 'manager_name', 'manager_user_id', 'latitude', 'longitude', 'status', 'capabilities', 'operating_hours']));
        return response()->json(['data' => $b, 'message' => 'تم تحديث الفرع']);
    }

    public function destroy(string $id): JsonResponse
    {
        Branch::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الفرع']);
    }

    public function stats(Request $r): JsonResponse
    {
        $aid = $r->user()->account_id;
        return response()->json(['data' => [
            'total' => Branch::where('account_id', $aid)->count(),
            'active' => Branch::where('account_id', $aid)->where('status', 'active')->count(),
            'by_type' => Branch::where('account_id', $aid)->selectRaw('branch_type, count(*) as count')->groupBy('branch_type')->pluck('count', 'branch_type'),
            'by_country' => Branch::where('account_id', $aid)->selectRaw('country, count(*) as count')->groupBy('country')->pluck('count', 'country'),
            'companies' => Company::where('account_id', $aid)->count(),
        ]]);
    }

    // ── Staff Assignment ─────────────────────────────────────────
    public function assignStaff(Request $r, string $id): JsonResponse
    {
        $r->validate(['user_id' => 'required|uuid|exists:users,id', 'role' => 'nullable|string|max:50']);
        BranchStaff::updateOrCreate(
            ['branch_id' => $id, 'user_id' => $r->user_id],
            ['role' => $r->role ?? 'agent', 'assigned_at' => now(), 'released_at' => null]
        );
        return response()->json(['message' => 'تم تعيين الموظف']);
    }

    public function removeStaff(string $id, string $userId): JsonResponse
    {
        BranchStaff::where('branch_id', $id)->where('user_id', $userId)->update(['released_at' => now()]);
        return response()->json(['message' => 'تم إلغاء تعيين الموظف']);
    }
}
