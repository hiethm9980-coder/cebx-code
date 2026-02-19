<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KycController — FR-IAM-014 + FR-IAM-016
 *
 * FR-IAM-014: KYC status display, capabilities, approve/reject
 * FR-IAM-016: Document access control, secure download, purge
 */
class KycController extends Controller
{
    public function __construct(
        protected KycService $kycService
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // FR-IAM-014: KYC Status & Capabilities
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/kyc/status
     * Comprehensive KYC status with capabilities and display info.
     */
    public function status(Request $request): JsonResponse
    {
        $status = $this->kycService->getKycStatus($request->user()->account_id);

        return response()->json([
            'success' => true,
            'data'    => $status,
        ]);
    }

    /**
     * POST /api/v1/kyc/approve
     * Approve a pending KYC submission. Requires kyc:manage.
     */
    public function approve(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => ['required', 'uuid'],
            'notes'      => ['nullable', 'string', 'max:1000'],
            'level'      => ['nullable', 'string', 'in:basic,enhanced,full'],
        ]);

        $kyc = $this->kycService->approveKyc(
            $request->account_id,
            $request->user(),
            $request->notes,
            $request->level
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على التحقق بنجاح.',
            'data'    => [
                'status'     => $kyc->status,
                'reviewed_at'=> $kyc->reviewed_at?->toISOString(),
                'expires_at' => $kyc->expires_at?->toISOString(),
            ],
        ]);
    }

    /**
     * POST /api/v1/kyc/reject
     * Reject a pending KYC submission. Requires kyc:manage.
     */
    public function reject(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => ['required', 'uuid'],
            'reason'     => ['required', 'string', 'max:1000'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $kyc = $this->kycService->rejectKyc(
            $request->account_id,
            $request->user(),
            $request->reason,
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض التحقق.',
            'data'    => [
                'status'           => $kyc->status,
                'rejection_reason' => $kyc->rejection_reason,
                'reviewed_at'      => $kyc->reviewed_at?->toISOString(),
            ],
        ]);
    }

    /**
     * POST /api/v1/kyc/resubmit
     * Re-submit after rejection/expiry.
     */
    public function resubmit(Request $request): JsonResponse
    {
        $request->validate([
            'documents'   => ['required', 'array', 'min:1'],
            'documents.*' => ['string', 'max:500'],
        ]);

        $kyc = $this->kycService->resubmitKyc(
            $request->user()->account_id,
            $request->documents,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تقديم الوثائق للمراجعة.',
            'data'    => [
                'status'       => $kyc->status,
                'submitted_at' => $kyc->submitted_at?->toISOString(),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-IAM-016: Document Access Control
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/kyc/documents
     * List KYC documents (requires kyc:documents permission).
     */
    public function listDocuments(Request $request): JsonResponse
    {
        $documents = $this->kycService->listDocuments(
            $request->user()->account_id,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data'    => $documents,
            'meta'    => ['count' => count($documents)],
        ]);
    }

    /**
     * POST /api/v1/kyc/documents/upload
     * Upload a KYC document.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'kyc_verification_id' => ['required', 'uuid'],
            'document_type'       => ['required', 'string', 'max:100'],
            'filename'            => ['required', 'string', 'max:255'],
            'stored_path'         => ['required', 'string', 'max:500'],
            'mime_type'           => ['required', 'string', 'max:100'],
            'file_size'           => ['required', 'integer', 'min:1'],
            'is_sensitive'        => ['nullable', 'boolean'],
        ]);

        $doc = $this->kycService->uploadDocument(
            $request->user()->account_id,
            $request->kyc_verification_id,
            $request->document_type,
            $request->filename,
            $request->stored_path,
            $request->mime_type,
            $request->file_size,
            $request->user(),
            $request->boolean('is_sensitive', true)
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الوثيقة بنجاح.',
            'data'    => [
                'id'            => $doc->id,
                'document_type' => $doc->document_type,
                'filename'      => $doc->original_filename,
            ],
        ], 201);
    }

    /**
     * GET /api/v1/kyc/documents/{id}/download
     * Get a temporary download URL for a KYC document.
     * Requires kyc:documents permission. Access is logged.
     */
    public function downloadDocument(Request $request, string $id): JsonResponse
    {
        $downloadInfo = $this->kycService->getDocumentDownloadUrl(
            $request->user()->account_id,
            $id,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data'    => $downloadInfo,
        ]);
    }

    /**
     * DELETE /api/v1/kyc/documents/{id}
     * Purge a KYC document (soft-delete content, keep metadata).
     * Requires kyc:manage permission.
     */
    public function purgeDocument(Request $request, string $id): JsonResponse
    {
        $doc = $this->kycService->purgeDocument(
            $request->user()->account_id,
            $id,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حذف محتوى الوثيقة. البيانات الوصفية محفوظة للتدقيق.',
            'data'    => [
                'id'        => $doc->id,
                'is_purged' => true,
                'purged_at' => $doc->purged_at?->toISOString(),
            ],
        ]);
    }
}
