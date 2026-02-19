<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ShipmentCharge;
use App\Services\PricingEngineService;
use App\Services\StatusTransitionService;
use App\Services\FraudDetectionService;
use App\Services\SmartRoutingService;
use App\Services\DynamicPricingService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * CBEX GROUP — Booking Workflow Controller
 *
 * Complete booking flow:
 * 1. Get quotes (routing + pricing)
 * 2. Create booking
 * 3. Fraud check
 * 4. Generate invoice
 * 5. Process payment
 * 6. Confirm booking
 */
class BookingController extends Controller
{
    public function __construct(
        protected PricingEngineService $pricing,
        protected StatusTransitionService $statusEngine,
        protected SmartRoutingService $routing,
        protected DynamicPricingService $dynamicPricing,
        protected FraudDetectionService $fraud,
        protected AuditService $audit,
    ) {}

    /**
     * Step 1: Get quotes — routes + dynamic pricing
     */
    public function getQuotes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'origin_city' => 'required|string',
            'origin_country' => 'required|string|size:2',
            'destination_city' => 'required|string',
            'destination_country' => 'required|string|size:2',
            'weight' => 'required|numeric|min:0.1',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'numeric',
            'dimensions.width' => 'numeric',
            'dimensions.height' => 'numeric',
            'service_level' => 'in:express,standard,economy',
            'preferred_mode' => 'nullable|in:air,sea,land',
            'items_count' => 'nullable|integer',
            'declared_value' => 'nullable|numeric',
            'insurance' => 'nullable|boolean',
        ]);

        // Get smart routes
        $routes = $this->routing->suggestRoutes($data);

        // Apply dynamic pricing to each route
        $quotes = [];
        foreach ($routes['routes'] as $route) {
            $dynamicPrice = $this->dynamicPricing->calculate([
                'base_price' => $route['estimated_cost'],
                'origin_country' => $data['origin_country'],
                'destination_country' => $data['destination_country'],
                'shipment_type' => $route['mode'],
                'weight' => $data['weight'],
                'service_level' => $data['service_level'] ?? 'standard',
            ]);

            $quotes[] = [
                'quote_id' => Str::uuid()->toString(),
                'route' => $route,
                'pricing' => $dynamicPrice,
                'total_price' => $dynamicPrice['total_price'],
                'currency' => 'SAR',
                'eta' => $route['eta'],
                'estimated_days' => $route['estimated_days'],
                'valid_until' => $dynamicPrice['valid_until'],
            ];
        }

        return response()->json([
            'data' => [
                'quotes' => $quotes,
                'total_options' => count($quotes),
                'params' => $data,
            ],
        ]);
    }

    /**
     * Step 2: Create booking from selected quote
     */
    public function createBooking(Request $request): JsonResponse
    {
        $data = $request->validate([
            'quote_id' => 'nullable|string',
            // Sender
            'sender_name' => 'required|string|max:200',
            'sender_phone' => 'required|string|max:30',
            'sender_email' => 'nullable|email',
            'sender_address' => 'required|string',
            'sender_city' => 'required|string',
            'sender_country' => 'required|string|size:2',
            'sender_postal_code' => 'nullable|string|max:20',
            // Receiver
            'receiver_name' => 'required|string|max:200',
            'receiver_phone' => 'required|string|max:30',
            'receiver_email' => 'nullable|email',
            'receiver_address' => 'required|string',
            'receiver_city' => 'required|string',
            'receiver_country' => 'required|string|size:2',
            'receiver_postal_code' => 'nullable|string|max:20',
            // Package
            'weight' => 'required|numeric|min:0.1',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'description' => 'nullable|string|max:500',
            'declared_value' => 'nullable|numeric',
            'insurance' => 'nullable|boolean',
            // Shipment
            'shipment_type' => 'required|in:air,sea,land',
            'service_level' => 'in:express,standard,economy',
            'incoterm' => 'nullable|string|max:10',
            'carrier_id' => 'nullable|uuid',
            'selected_rate_id' => 'nullable|string',
            // Items
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.weight' => 'nullable|numeric',
            'items.*.hs_code' => 'nullable|string|max:20',
            'items.*.dangerous' => 'nullable|boolean',
            'items.*.value' => 'nullable|numeric',
        ]);

        $user = $request->user();

        // Calculate chargeable weight
        $volumetricWeight = 0;
        if ($data['length'] ?? 0 && $data['width'] ?? 0 && $data['height'] ?? 0) {
            $volumetricWeight = ($data['length'] * $data['width'] * $data['height']) / 5000;
        }
        $chargeableWeight = max($data['weight'], $volumetricWeight);

        // Create shipment
        $shipment = Shipment::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $user->account_id,
            'tracking_number' => 'CBX' . strtoupper(Str::random(10)),
            'customer_id' => $user->id,
            'origin_country' => $data['sender_country'],
            'destination_country' => $data['receiver_country'],
            'shipment_type' => $data['shipment_type'],
            'service_level' => $data['service_level'] ?? 'standard',
            'incoterm' => $data['incoterm'] ?? 'DAP',
            'total_weight' => $data['weight'],
            'total_volume' => $volumetricWeight,
            'chargeable_weight' => $chargeableWeight,
            'declared_value' => $data['declared_value'] ?? 0,
            'insurance_flag' => $data['insurance'] ?? false,
            'status' => 'created',
            'sender_name' => $data['sender_name'],
            'sender_phone' => $data['sender_phone'],
            'sender_address' => $data['sender_address'],
            'sender_city' => $data['sender_city'],
            'receiver_name' => $data['receiver_name'],
            'receiver_phone' => $data['receiver_phone'],
            'receiver_address' => $data['receiver_address'],
            'receiver_city' => $data['receiver_city'],
            'description' => $data['description'] ?? null,
            'carrier_id' => $data['carrier_id'] ?? null,
        ]);

        // Create shipment items
        foreach ($data['items'] ?? [] as $item) {
            $shipment->items()->create([
                'id' => Str::uuid()->toString(),
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'weight' => $item['weight'] ?? null,
                'hs_code' => $item['hs_code'] ?? null,
                'dangerous_flag' => $item['dangerous'] ?? false,
                'value' => $item['value'] ?? null,
            ]);
        }

        // Run fraud detection
        $fraudResult = $this->fraud->scan($shipment);
        if ($fraudResult['level'] === 'blocked') {
            $shipment->update(['status' => 'held', 'hold_reason' => 'fraud_check']);
            return response()->json([
                'data' => $shipment,
                'warning' => 'الشحنة قيد المراجعة الأمنية',
                'fraud_check' => $fraudResult,
            ], 202);
        }

        // Generate invoice
        $invoice = $this->generateInvoice($shipment, $data);

        // Transition to booked
        $this->statusEngine->transition($shipment, 'booked', [
            'user_id' => $user->id,
            'notes' => 'حجز جديد عبر المنصة',
        ]);

        $this->audit->log('booking.created', $shipment);

        return response()->json([
            'data' => $shipment->fresh()->load('items'),
            'invoice' => $invoice,
            'message' => "تم الحجز بنجاح — #{$shipment->tracking_number}",
        ], 201);
    }

    /**
     * Confirm booking (after payment)
     */
    public function confirmBooking(Request $request, string $id): JsonResponse
    {
        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);

        if (!in_array($shipment->status, ['booked', 'created'])) {
            return response()->json(['message' => 'الشحنة غير قابلة للتأكيد في حالتها الحالية'], 422);
        }

        // Verify payment
        $invoice = Invoice::where('shipment_id', $shipment->id)->first();
        if ($invoice && $invoice->status !== 'paid') {
            return response()->json(['message' => 'يجب دفع الفاتورة أولاً', 'invoice' => $invoice], 422);
        }

        $shipment->update(['confirmed_at' => now()]);
        $this->audit->log('booking.confirmed', $shipment);

        return response()->json([
            'data' => $shipment->fresh(),
            'message' => 'تم تأكيد الحجز',
        ]);
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(Request $request, string $id): JsonResponse
    {
        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($id);

        $cancellable = ['created', 'booked'];
        if (!in_array($shipment->status, $cancellable)) {
            return response()->json(['message' => 'لا يمكن إلغاء الشحنة في هذه المرحلة'], 422);
        }

        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        $this->statusEngine->transition($shipment, 'cancelled', [
            'user_id' => $request->user()->id,
            'notes' => $data['reason'] ?? 'إلغاء بواسطة العميل',
        ]);

        // Cancel invoice
        Invoice::where('shipment_id', $shipment->id)->update(['status' => 'cancelled']);

        // TODO: Process refund if payment was made

        $this->audit->log('booking.cancelled', $shipment);

        return response()->json(['data' => $shipment->fresh(), 'message' => 'تم إلغاء الحجز']);
    }

    /**
     * Generate invoice for a booking
     */
    protected function generateInvoice(Shipment $shipment, array $data): Invoice
    {
        // Calculate charges
        $charges = $this->pricing->calculate([
            'origin' => $data['sender_city'],
            'destination' => $data['receiver_city'],
            'weight' => $shipment->chargeable_weight,
            'shipment_type' => $shipment->shipment_type,
            'service_level' => $shipment->service_level,
        ]);

        $totalAmount = $charges['total'] ?? 0;

        $invoice = Invoice::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $shipment->account_id,
            'shipment_id' => $shipment->id,
            'customer_id' => $shipment->customer_id,
            'invoice_number' => 'INV-' . date('Ym') . '-' . strtoupper(Str::random(6)),
            'total_amount' => $totalAmount,
            'currency' => 'SAR',
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        // Create line items
        foreach ($charges['items'] ?? [] as $item) {
            InvoiceItem::create([
                'id' => Str::uuid(),
                'invoice_id' => $invoice->id,
                'description' => $item['description'] ?? 'رسوم شحن',
                'quantity' => 1,
                'unit_price' => $item['amount'] ?? 0,
                'total' => $item['amount'] ?? 0,
            ]);
        }

        return $invoice;
    }
}
