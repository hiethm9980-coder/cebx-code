<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\KycComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KycComplianceController — FR-KYC-001→008
 */
class KycComplianceController extends Controller
{
    public function __construct(private KycComplianceService $service) {}

    // ═══════════════ FR-KYC-001: Create/Get Case ═════════════

    public function createCase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_type'     => 'required|in:individual,organization',
            'organization_id'  => 'nullable|uuid',
            'applicant_name'   => 'nullable|string|max:300',
            'applicant_email'  => 'nullable|email',
            'country_code'     => 'nullable|string|size:2',
        ]);
        $case = $this->service->createCase($request->user()->account_id, $data['account_type'], $data);
        return response()->json(['status' => 'success', 'data' => $case], 201);
    }

    public function getCase(string $caseId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->getCase($caseId)]);
    }

    public function getStatus(Request $request): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->getVerificationStatus($request->user()->account_id)]);
    }

    // ═══════════════ FR-KYC-002: Upload Documents ════════════

    public function uploadDocument(Request $request, string $caseId): JsonResponse
    {
        $data = $request->validate([
            'document_type'     => 'required|in:national_id,passport,commercial_register,tax_certificate,bank_statement,utility_bill,other',
            'original_filename' => 'required|string|max:500',
            'stored_path'       => 'required|string|max:1000',
            'mime_type'         => 'required|string|max:100',
            'file_size'         => 'required|integer|max:10485760',
        ]);
        $doc = $this->service->uploadDocument($caseId, $data, $request->user()->id);
        return response()->json(['status' => 'success', 'data' => $doc], 201);
    }

    // ═══════════════ FR-KYC-003: Submit for Review ═══════════

    public function submit(Request $request, string $caseId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->submitForReview($caseId, $request->user()->id)]);
    }

    // ═══════════════ FR-KYC-004: Restrictions ════════════════

    public function checkRestriction(Request $request): JsonResponse
    {
        $data = $request->validate(['feature_key' => 'required|string']);
        return response()->json(['status' => 'success', 'data' => $this->service->checkRestriction($request->user()->account_id, $data['feature_key'])]);
    }

    public function listRestrictions(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->listRestrictions()]);
    }

    public function createRestriction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:200', 'restriction_key' => 'required|string|max:100|unique:verification_restrictions',
            'applies_to_statuses' => 'required|array', 'restriction_type' => 'required|in:block_feature,quota_limit',
            'quota_value' => 'nullable|integer|min:1', 'feature_key' => 'nullable|string|max:100',
        ]);
        return response()->json(['status' => 'success', 'data' => $this->service->createRestriction($data)], 201);
    }

    // ═══════════════ FR-KYC-005: Admin Review ════════════════

    public function listPending(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->listPendingCases()]);
    }

    public function review(Request $request, string $caseId): JsonResponse
    {
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected,needs_more_info',
            'reason'   => 'nullable|string', 'document_decisions' => 'nullable|array',
        ]);
        $case = $this->service->reviewCase($caseId, $request->user()->id, $data['decision'], $data['reason'] ?? null, $data['document_decisions'] ?? null);
        return response()->json(['status' => 'success', 'data' => $case]);
    }

    // ═══════════════ FR-KYC-006: Status Display ══════════════

    public function statusDisplay(Request $request): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->getStatusDisplay($request->user()->account_id)]);
    }

    // ═══════════════ FR-KYC-007: Document Download ═══════════

    public function downloadDocument(Request $request, string $documentId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => ['url' => $this->service->getDocumentDownloadUrl($documentId, $request->user()->id)]]);
    }

    // ═══════════════ FR-KYC-008: Audit Log ═══════════════════

    public function auditLog(string $caseId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->service->getAuditLog($caseId)]);
    }

    public function exportAuditLog(Request $request): JsonResponse
    {
        $data = $request->validate(['case_id' => 'nullable|uuid', 'from' => 'nullable|date', 'to' => 'nullable|date']);
        return response()->json(['status' => 'success', 'data' => $this->service->exportAuditLog($data['case_id'] ?? null, $data['from'] ?? null, $data['to'] ?? null)]);
    }
}
