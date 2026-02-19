<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ShipmentController — FR-SH-001→019 API endpoints
 */
class ShipmentController extends Controller
{
    public function __construct(protected ShipmentService $shipmentService) {}

    // ── FR-SH-001: Create Direct Shipment ────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id'            => 'nullable|uuid',
            'sender_name'         => 'required|string|max:200',
            'sender_company'      => 'nullable|string|max:200',
            'sender_phone'        => 'required|string|max:30',
            'sender_email'        => 'nullable|email|max:255',
            'sender_address_1'    => 'required|string|max:300',
            'sender_address_2'    => 'nullable|string|max:300',
            'sender_city'         => 'required|string|max:100',
            'sender_state'        => 'nullable|string|max:100',
            'sender_postal_code'  => 'nullable|string|max:20',
            'sender_country'      => 'required|string|size:2',
            'sender_address_id'   => 'nullable|uuid',
            'recipient_name'      => 'required|string|max:200',
            'recipient_company'   => 'nullable|string|max:200',
            'recipient_phone'     => 'required|string|max:30',
            'recipient_email'     => 'nullable|email|max:255',
            'recipient_address_1' => 'required|string|max:300',
            'recipient_address_2' => 'nullable|string|max:300',
            'recipient_city'      => 'required|string|max:100',
            'recipient_state'     => 'nullable|string|max:100',
            'recipient_postal_code' => 'nullable|string|max:20',
            'recipient_country'   => 'required|string|size:2',
            'recipient_address_id'=> 'nullable|uuid',
            'cod_amount'          => 'nullable|numeric|min:0',
            'insurance_amount'    => 'nullable|numeric|min:0',
            'is_return'           => 'nullable|boolean',
            'has_dangerous_goods' => 'nullable|boolean',
            'delivery_instructions' => 'nullable|string|max:500',
            'parcels'             => 'required|array|min:1|max:50',
            'parcels.*.weight'    => 'required|numeric|min:0.01|max:999',
            'parcels.*.length'    => 'nullable|numeric|min:0.1|max:999',
            'parcels.*.width'     => 'nullable|numeric|min:0.1|max:999',
            'parcels.*.height'    => 'nullable|numeric|min:0.1|max:999',
            'parcels.*.packaging_type' => 'nullable|string|in:box,envelope,tube,custom',
            'parcels.*.description'    => 'nullable|string|max:300',
            'metadata'            => 'nullable|array',
        ]);

        $shipment = $this->shipmentService->createDirect(
            $request->user()->account_id, $data, $request->user()
        );

        return response()->json(['data' => $shipment], 201);
    }

    // ── FR-SH-002: Create from Order ─────────────────────────────
    public function createFromOrder(Request $request, string $orderId): JsonResponse
    {
        $overrides = $request->validate([
            'sender_name'         => 'nullable|string|max:200',
            'sender_phone'        => 'nullable|string|max:30',
            'sender_address_1'    => 'nullable|string|max:300',
            'sender_city'         => 'nullable|string|max:100',
            'sender_country'      => 'nullable|string|size:2',
            'parcels'             => 'nullable|array|min:1',
            'parcels.*.weight'    => 'required_with:parcels|numeric|min:0.01',
            'parcels.*.length'    => 'nullable|numeric|min:0.1',
            'parcels.*.width'     => 'nullable|numeric|min:0.1',
            'parcels.*.height'    => 'nullable|numeric|min:0.1',
        ]);

        $shipment = $this->shipmentService->createFromOrder(
            $request->user()->account_id, $orderId, $overrides, $request->user()
        );

        return response()->json(['data' => $shipment], 201);
    }

    // ── FR-SH-005: Validate ──────────────────────────────────────
    public function validate(Request $request, string $id): JsonResponse
    {
        $shipment = $this->shipmentService->validateShipment(
            $request->user()->account_id, $id, $request->user()
        );
        return response()->json(['data' => $shipment]);
    }

    // ── FR-SH-006: Update Status ─────────────────────────────────
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $shipment = $this->shipmentService->updateStatus(
            $request->user()->account_id, $id, $data['status'], $request->user(), $data['reason'] ?? null
        );

        return response()->json(['data' => $shipment]);
    }

    // ── FR-SH-007: Cancel ────────────────────────────────────────
    public function cancel(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        $shipment = $this->shipmentService->cancelShipment(
            $request->user()->account_id, $id, $request->user(), $data['reason'] ?? null
        );

        return response()->json(['data' => $shipment]);
    }

    // ── FR-SH-008: Get Label ─────────────────────────────────────
    public function label(Request $request, string $id): JsonResponse
    {
        $info = $this->shipmentService->getLabelInfo(
            $request->user()->account_id, $id, $request->user()
        );
        return response()->json(['data' => $info]);
    }

    // ── FR-SH-009: List / Search / Filter ────────────────────────
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'store_id'         => 'nullable|uuid',
            'status'           => 'nullable|string',
            'carrier'          => 'nullable|string',
            'source'           => 'nullable|string|in:direct,order,bulk,return',
            'is_cod'           => 'nullable|boolean',
            'is_international' => 'nullable|boolean',
            'from'             => 'nullable|date',
            'to'               => 'nullable|date',
            'search'           => 'nullable|string|max:200',
            'limit'            => 'nullable|integer|min:1|max:100',
            'offset'           => 'nullable|integer|min:0',
        ]);

        $result = $this->shipmentService->listShipments(
            $request->user()->account_id, $filters, $request->user()
        );

        return response()->json(['data' => $result]);
    }

    // ── FR-SH-009: Single Shipment ───────────────────────────────
    public function show(Request $request, string $id): JsonResponse
    {
        $shipment = $this->shipmentService->getShipment(
            $request->user()->account_id, $id
        );
        return response()->json(['data' => $shipment]);
    }

    // ── Statistics ───────────────────────────────────────────────
    public function stats(Request $request): JsonResponse
    {
        $storeId = $request->query('store_id');
        $stats = $this->shipmentService->getShipmentStats(
            $request->user()->account_id, $storeId
        );
        return response()->json(['data' => $stats]);
    }

    // ── FR-SH-010: Bulk Create ───────────────────────────────────
    public function bulkCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_ids'       => 'required|array|min:1|max:100',
            'order_ids.*'     => 'uuid',
            'defaults'        => 'nullable|array',
            'defaults.sender_name'      => 'nullable|string',
            'defaults.sender_phone'     => 'nullable|string',
            'defaults.sender_address_1' => 'nullable|string',
            'defaults.sender_city'      => 'nullable|string',
            'defaults.sender_country'   => 'nullable|string|size:2',
        ]);

        $result = $this->shipmentService->bulkCreateFromOrders(
            $request->user()->account_id, $data['order_ids'], $data['defaults'] ?? [], $request->user()
        );

        return response()->json(['data' => $result]);
    }

    // ── FR-SH-016: Create Return ─────────────────────────────────
    public function createReturn(Request $request, string $id): JsonResponse
    {
        $overrides = $request->validate([
            'parcels'          => 'nullable|array|min:1',
            'parcels.*.weight' => 'required_with:parcels|numeric|min:0.01',
        ]);

        $shipment = $this->shipmentService->createReturnShipment(
            $request->user()->account_id, $id, $overrides, $request->user()
        );

        return response()->json(['data' => $shipment], 201);
    }

    // ── FR-SH-003: Add Parcel ────────────────────────────────────
    public function addParcel(Request $request, string $shipmentId): JsonResponse
    {
        $data = $request->validate([
            'weight'         => 'required|numeric|min:0.01|max:999',
            'length'         => 'nullable|numeric|min:0.1|max:999',
            'width'          => 'nullable|numeric|min:0.1|max:999',
            'height'         => 'nullable|numeric|min:0.1|max:999',
            'packaging_type' => 'nullable|string|in:box,envelope,tube,custom',
            'description'    => 'nullable|string|max:300',
            'reference'      => 'nullable|string|max:100',
        ]);

        $parcel = $this->shipmentService->addParcel(
            $request->user()->account_id, $shipmentId, $data, $request->user()
        );

        return response()->json(['data' => $parcel], 201);
    }

    // ── FR-SH-003: Remove Parcel ─────────────────────────────────
    public function removeParcel(Request $request, string $shipmentId, string $parcelId): JsonResponse
    {
        $this->shipmentService->removeParcel(
            $request->user()->account_id, $shipmentId, $parcelId, $request->user()
        );

        return response()->json(['message' => 'تم حذف الطرد بنجاح.']);
    }
}
