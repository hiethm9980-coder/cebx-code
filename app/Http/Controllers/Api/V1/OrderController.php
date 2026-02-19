<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OrderController — FR-ST module API endpoints.
 *
 * Orders: list, show, create (manual), status update, cancel, stats
 * Store Sync: test connection, register webhooks, trigger sync
 */
class OrderController extends Controller
{
    public function __construct(
        protected OrderService $service
    ) {}

    // ─── Orders ──────────────────────────────────────────────────

    /**
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['store_id', 'status', 'source', 'search', 'from', 'to', 'limit', 'offset']);

        $result = $this->service->listOrders(
            $request->user()->account_id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data'    => $result['orders'],
            'meta'    => [
                'total'  => $result['total'],
                'limit'  => $result['limit'],
                'offset' => $result['offset'],
            ],
        ]);
    }

    /**
     * GET /api/v1/orders/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->service->getOrderStats(
            $request->user()->account_id,
            $request->query('store_id')
        );

        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * GET /api/v1/orders/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $order = $this->service->getOrder(
            $request->user()->account_id, $id
        );

        return response()->json(['success' => true, 'data' => $order]);
    }

    /**
     * POST /api/v1/orders (manual creation)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'store_id'               => 'required|uuid',
            'customer_name'          => 'required|string|max:200',
            'customer_email'         => 'sometimes|email|max:255',
            'customer_phone'         => 'sometimes|string|max:30',
            'shipping_name'          => 'sometimes|string|max:200',
            'shipping_phone'         => 'sometimes|string|max:30',
            'shipping_address_line_1' => 'required|string|max:300',
            'shipping_city'          => 'required|string|max:100',
            'shipping_country'       => 'required|string|size:2',
            'shipping_postal_code'   => 'sometimes|string|max:20',
            'items'                  => 'required|array|min:1',
            'items.*.name'           => 'required|string|max:300',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.weight'         => 'sometimes|numeric|min:0',
            'items.*.sku'            => 'sometimes|string|max:100',
            'currency'               => 'sometimes|string|size:3',
        ]);

        $order = $this->service->createManualOrder(
            $request->user()->account_id,
            $request->store_id,
            $request->all(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح.',
            'data'    => $order,
        ], 201);
    }

    /**
     * PUT /api/v1/orders/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,ready,processing,shipped,delivered,cancelled,on_hold,failed',
            'reason' => 'sometimes|string|max:500',
        ]);

        $order = $this->service->updateOrderStatus(
            $request->user()->account_id,
            $id,
            $request->status,
            $request->user(),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب.',
            'data'    => $order,
        ]);
    }

    /**
     * POST /api/v1/orders/{id}/cancel
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'sometimes|string|max:500']);

        $order = $this->service->cancelOrder(
            $request->user()->account_id,
            $id,
            $request->user(),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب.',
            'data'    => $order,
        ]);
    }

    // ─── Store Connection & Sync ─────────────────────────────────

    /**
     * POST /api/v1/stores/{id}/test-connection
     */
    public function testConnection(Request $request, string $id): JsonResponse
    {
        $store = Store::where('id', $id)
            ->where('account_id', $request->user()->account_id)
            ->firstOrFail();

        $result = $this->service->testStoreConnection($store, $request->user());

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /api/v1/stores/{id}/register-webhooks
     */
    public function registerWebhooks(Request $request, string $id): JsonResponse
    {
        $store = Store::where('id', $id)
            ->where('account_id', $request->user()->account_id)
            ->firstOrFail();

        $result = $this->service->registerWebhooks($store, $request->user());

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /api/v1/stores/{id}/sync
     */
    public function syncStore(Request $request, string $id): JsonResponse
    {
        $store = Store::where('id', $id)
            ->where('account_id', $request->user()->account_id)
            ->firstOrFail();

        $syncLog = $this->service->syncStore($store, $request->user(), $request->all());

        return response()->json([
            'success' => true,
            'message' => 'تمت المزامنة.',
            'data'    => $syncLog,
        ]);
    }
}
