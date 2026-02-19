<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DgComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DgComplianceController — FR-DG-001→009
 *
 * API endpoints for dangerous goods compliance and content declaration.
 */
class DgComplianceController extends Controller
{
    public function __construct(private DgComplianceService $service) {}

    // ── FR-DG-001: Create Declaration ────────────────────────

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|string|max:100',
            'locale'      => 'nullable|string|in:ar,en',
        ]);

        $declaration = $this->service->createDeclaration(
            accountId:  $request->user()->account_id,
            shipmentId: $request->shipment_id,
            declaredBy: $request->user()->id,
            locale:     $request->locale ?? 'ar',
            ipAddress:  $request->ip(),
            userAgent:  $request->userAgent(),
        );

        return response()->json(['data' => $declaration], 201);
    }

    // ── FR-DG-002: Set DG Flag ──────────────────────────────

    public function setDgFlag(Request $request, string $declarationId): JsonResponse
    {
        $request->validate([
            'contains_dangerous_goods' => 'required|boolean',
        ]);

        $declaration = $this->service->setDgFlag(
            declarationId: $declarationId,
            containsDg:    $request->boolean('contains_dangerous_goods'),
            actorId:       $request->user()->id,
            ipAddress:     $request->ip(),
        );

        return response()->json(['data' => $declaration]);
    }

    // ── FR-DG-004: Accept Waiver ────────────────────────────

    public function acceptWaiver(Request $request, string $declarationId): JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string|in:ar,en',
        ]);

        $declaration = $this->service->acceptWaiver(
            declarationId: $declarationId,
            actorId:       $request->user()->id,
            locale:        $request->locale,
            ipAddress:     $request->ip(),
        );

        return response()->json(['data' => $declaration]);
    }

    // ── FR-DG-007: Validate for Issuance ────────────────────

    public function validateForIssuance(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|string|max:100',
        ]);

        try {
            $declaration = $this->service->validateForIssuance(
                $request->shipment_id,
                $request->user()->account_id,
            );
            return response()->json(['data' => ['valid' => true, 'declaration_id' => $declaration->id]]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage(), 'valid' => false], 422);
        }
    }

    // ── FR-DG-003: Hold Info ────────────────────────────────

    public function holdInfo(string $declarationId): JsonResponse
    {
        $info = $this->service->getHoldInfo($declarationId);
        return response()->json(['data' => $info]);
    }

    // ── FR-DG-008: Get Declaration (RBAC-aware) ─────────────

    public function show(Request $request, string $declarationId): JsonResponse
    {
        // TODO: Check permissions for full detail vs summary
        $fullDetail = true; // Simplified for now
        $data = $this->service->getDeclaration($declarationId, $fullDetail, $request->user()->id);
        return response()->json(['data' => $data]);
    }

    public function forShipment(Request $request, string $shipmentId): JsonResponse
    {
        $declaration = $this->service->getDeclarationForShipment($shipmentId, $request->user()->account_id);

        if (!$declaration) {
            return response()->json(['error' => 'No declaration found for this shipment'], 404);
        }

        return response()->json(['data' => $declaration]);
    }

    // ── FR-DG-009: Save DG Metadata ─────────────────────────

    public function saveDgMetadata(Request $request, string $declarationId): JsonResponse
    {
        $request->validate([
            'un_number'            => 'nullable|string|max:10',
            'dg_class'             => 'nullable|string|max:20',
            'packing_group'        => 'nullable|string|in:I,II,III',
            'proper_shipping_name' => 'nullable|string|max:300',
            'quantity'             => 'nullable|numeric|min:0',
            'quantity_unit'        => 'nullable|string|max:20',
            'description'          => 'nullable|string|max:1000',
        ]);

        $metadata = $this->service->saveDgMetadata(
            $declarationId,
            $request->only(['un_number', 'dg_class', 'packing_group', 'proper_shipping_name', 'quantity', 'quantity_unit', 'description']),
            $request->user()->id,
            $request->ip(),
        );

        return response()->json(['data' => $metadata]);
    }

    // ── FR-DG-006: Waiver Version Management ────────────────

    public function publishWaiver(Request $request): JsonResponse
    {
        $request->validate([
            'version'     => 'required|string|max:20',
            'locale'      => 'required|string|in:ar,en',
            'waiver_text' => 'required|string',
        ]);

        $waiver = $this->service->publishWaiverVersion(
            $request->version,
            $request->locale,
            $request->waiver_text,
            $request->user()->id,
        );

        return response()->json(['data' => $waiver], 201);
    }

    public function activeWaiver(Request $request): JsonResponse
    {
        $locale = $request->query('locale', 'ar');
        $waiver = $this->service->getActiveWaiver($locale);

        if (!$waiver) {
            return response()->json(['error' => 'No active waiver found'], 404);
        }

        return response()->json(['data' => $waiver]);
    }

    public function listWaiverVersions(Request $request): JsonResponse
    {
        $locale = $request->query('locale', 'ar');
        $versions = $this->service->listWaiverVersions($locale);
        return response()->json(['data' => $versions]);
    }

    // ── FR-DG-005: Audit Log ────────────────────────────────

    public function auditLog(Request $request, string $declarationId): JsonResponse
    {
        // FR-DG-008: Log the view action
        $log = $this->service->getAuditLog($declarationId);
        return response()->json($log);
    }

    public function shipmentAuditLog(Request $request, string $shipmentId): JsonResponse
    {
        $log = $this->service->getShipmentAuditLog($shipmentId);
        return response()->json($log);
    }

    public function exportAuditLog(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to', 'action']);
        $export = $this->service->exportAuditLog($request->user()->account_id, $filters);
        return response()->json(['data' => $export]);
    }

    // ── Listing ─────────────────────────────────────────────

    public function list(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'contains_dg']);
        $declarations = $this->service->listDeclarations($request->user()->account_id, $filters);
        return response()->json($declarations);
    }

    public function listBlocked(Request $request): JsonResponse
    {
        $blocked = $this->service->listBlockedShipments($request->user()->account_id);
        return response()->json(['data' => $blocked]);
    }
}
