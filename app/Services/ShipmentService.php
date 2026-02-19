<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Address;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Parcel;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Models\Store;
use App\Models\User;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

/**
 * ShipmentService — FR-SH-001→019 (19 requirements)
 *
 * FR-SH-001: Direct shipping
 * FR-SH-002: Order→Shipment
 * FR-SH-003: Multi-parcel
 * FR-SH-004: Address book
 * FR-SH-005: Validation
 * FR-SH-006: State machine
 * FR-SH-007: Cancel/Void
 * FR-SH-008: Reprint label
 * FR-SH-009: Search & filter
 * FR-SH-010: Bulk creation
 * FR-SH-011: Financial visibility (RBAC)
 * FR-SH-012: Print permissions
 * FR-SH-013: KYC check before purchase
 * FR-SH-014: Balance reservation
 * FR-SH-015: Ledger entries for charges/refunds
 * FR-SH-016: Return shipments
 * FR-SH-017: Dangerous goods flag
 * FR-SH-018: DG declaration status display
 * FR-SH-019: COD support
 */
class ShipmentService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-001: Direct Shipping (create from scratch)
    // ═══════════════════════════════════════════════════════════════

    public function createDirect(string $accountId, array $data, User $performer): Shipment
    {
        $this->assertCanManageShipments($performer);

        return DB::transaction(function () use ($accountId, $data, $performer) {
            $shipment = Shipment::create([
                'account_id'       => $accountId,
                'store_id'         => $data['store_id'] ?? null,
                'reference_number' => Shipment::generateReference(),
                'source'           => Shipment::SOURCE_DIRECT,
                'status'           => Shipment::STATUS_DRAFT,

                // Sender
                'sender_address_id'  => $data['sender_address_id'] ?? null,
                'sender_name'        => $data['sender_name'],
                'sender_company'     => $data['sender_company'] ?? null,
                'sender_phone'       => $data['sender_phone'],
                'sender_email'       => $data['sender_email'] ?? null,
                'sender_address_1'   => $data['sender_address_1'],
                'sender_address_2'   => $data['sender_address_2'] ?? null,
                'sender_city'        => $data['sender_city'],
                'sender_state'       => $data['sender_state'] ?? null,
                'sender_postal_code' => $data['sender_postal_code'] ?? null,
                'sender_country'     => $data['sender_country'],

                // Recipient
                'recipient_address_id'  => $data['recipient_address_id'] ?? null,
                'recipient_name'        => $data['recipient_name'],
                'recipient_company'     => $data['recipient_company'] ?? null,
                'recipient_phone'       => $data['recipient_phone'],
                'recipient_email'       => $data['recipient_email'] ?? null,
                'recipient_address_1'   => $data['recipient_address_1'],
                'recipient_address_2'   => $data['recipient_address_2'] ?? null,
                'recipient_city'        => $data['recipient_city'],
                'recipient_state'       => $data['recipient_state'] ?? null,
                'recipient_postal_code' => $data['recipient_postal_code'] ?? null,
                'recipient_country'     => $data['recipient_country'],

                // Flags
                'is_international'    => ($data['sender_country'] ?? 'SA') !== ($data['recipient_country'] ?? 'SA'),
                'is_cod'              => !empty($data['cod_amount']) && $data['cod_amount'] > 0,
                'cod_amount'          => $data['cod_amount'] ?? 0,
                'is_insured'          => !empty($data['insurance_amount']) && $data['insurance_amount'] > 0,
                'insurance_amount'    => $data['insurance_amount'] ?? 0,
                'is_return'           => $data['is_return'] ?? false,
                'has_dangerous_goods' => $data['has_dangerous_goods'] ?? false,
                'delivery_instructions' => $data['delivery_instructions'] ?? null,

                'created_by' => $performer->id,
                'metadata'   => $data['metadata'] ?? null,
            ]);

            // Create parcels (FR-SH-003)
            $this->createParcels($shipment, $data['parcels'] ?? [['weight' => $data['weight'] ?? 0.5]]);

            // Record initial status
            $this->recordStatusChange($shipment, null, Shipment::STATUS_DRAFT, 'system', $performer->id, 'Shipment created');

            $this->auditService->info(
                $accountId, $performer->id,
                'shipment.created', AuditLog::CATEGORY_ACCOUNT,
                'Shipment', $shipment->id,
                null,
                ['source' => 'direct', 'reference' => $shipment->reference_number]
            );

            return $shipment->load('parcels');
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-002: Order → Shipment
    // ═══════════════════════════════════════════════════════════════

    public function createFromOrder(string $accountId, string $orderId, array $overrides, User $performer): Shipment
    {
        $this->assertCanManageShipments($performer);

        $order = Order::where('account_id', $accountId)
            ->where('id', $orderId)
            ->with('items')
            ->firstOrFail();

        if (!$order->isShippable()) {
            throw new BusinessException('الطلب غير جاهز للشحن.', 'ERR_ORDER_NOT_SHIPPABLE', 422);
        }

        if ($order->hasShipment()) {
            throw new BusinessException('الطلب مرتبط بشحنة بالفعل.', 'ERR_ORDER_HAS_SHIPMENT', 422);
        }

        // Get default sender address
        $senderAddr = Address::where('account_id', $accountId)->defaultSender()->first();

        $data = array_merge([
            'store_id'            => $order->store_id,
            'sender_name'         => $senderAddr->contact_name ?? $overrides['sender_name'] ?? '',
            'sender_phone'        => $senderAddr->phone ?? $overrides['sender_phone'] ?? '',
            'sender_address_1'    => $senderAddr->address_line_1 ?? $overrides['sender_address_1'] ?? '',
            'sender_city'         => $senderAddr->city ?? $overrides['sender_city'] ?? '',
            'sender_country'      => $senderAddr->country ?? $overrides['sender_country'] ?? 'SA',
            'sender_address_id'   => $senderAddr->id ?? null,
            'recipient_name'      => $order->shipping_name ?? $order->customer_name,
            'recipient_phone'     => $order->shipping_phone ?? $order->customer_phone,
            'recipient_email'     => $order->customer_email,
            'recipient_address_1' => $order->shipping_address_line_1,
            'recipient_address_2' => $order->shipping_address_line_2,
            'recipient_city'      => $order->shipping_city,
            'recipient_state'     => $order->shipping_state,
            'recipient_postal_code' => $order->shipping_postal_code,
            'recipient_country'   => $order->shipping_country,
            'weight'              => $order->total_weight ?? 0.5,
        ], $overrides);

        return DB::transaction(function () use ($accountId, $data, $performer, $order) {
            $shipment = $this->createDirect($accountId, $data, $performer);

            // Link order ↔ shipment
            $shipment->update(['order_id' => $order->id, 'source' => Shipment::SOURCE_ORDER]);
            $order->update(['shipment_id' => $shipment->id, 'status' => Order::STATUS_PROCESSING]);

            $this->auditService->info(
                $accountId, $performer->id,
                'shipment.created_from_order', AuditLog::CATEGORY_ACCOUNT,
                'Shipment', $shipment->id,
                null,
                ['order_id' => $order->id, 'reference' => $shipment->reference_number]
            );

            return $shipment->fresh(['parcels', 'order']);
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-005: Validation
    // ═══════════════════════════════════════════════════════════════

    public function validateShipment(string $accountId, string $shipmentId, User $performer): Shipment
    {
        $shipment = $this->findShipment($accountId, $shipmentId);

        if ($shipment->status !== Shipment::STATUS_DRAFT) {
            throw new BusinessException('يمكن التحقق فقط من الشحنات في حالة مسودة.', 'ERR_INVALID_STATE', 422);
        }

        $errors = $this->runValidation($shipment);

        if (!empty($errors)) {
            throw new BusinessException(
                'فشل التحقق: ' . implode('، ', $errors),
                'ERR_VALIDATION_FAILED', 422
            );
        }

        $this->transitionStatus($shipment, Shipment::STATUS_VALIDATED, 'system', $performer->id, 'Validation passed');

        return $shipment->fresh();
    }

    private function runValidation(Shipment $shipment): array
    {
        $errors = [];

        // Sender validation
        if (empty($shipment->sender_name))      $errors[] = 'اسم المرسل مطلوب';
        if (empty($shipment->sender_phone))      $errors[] = 'هاتف المرسل مطلوب';
        if (empty($shipment->sender_address_1))  $errors[] = 'عنوان المرسل مطلوب';
        if (empty($shipment->sender_city))       $errors[] = 'مدينة المرسل مطلوبة';
        if (empty($shipment->sender_country))    $errors[] = 'دولة المرسل مطلوبة';

        // Recipient validation
        if (empty($shipment->recipient_name))      $errors[] = 'اسم المستلم مطلوب';
        if (empty($shipment->recipient_phone))      $errors[] = 'هاتف المستلم مطلوب';
        if (empty($shipment->recipient_address_1))  $errors[] = 'عنوان المستلم مطلوب';
        if (empty($shipment->recipient_city))       $errors[] = 'مدينة المستلم مطلوبة';
        if (empty($shipment->recipient_country))    $errors[] = 'دولة المستلم مطلوبة';

        // Parcel validation
        if ($shipment->parcels->isEmpty()) {
            $errors[] = 'يجب إضافة طرد واحد على الأقل';
        } else {
            foreach ($shipment->parcels as $parcel) {
                if ($parcel->weight <= 0) {
                    $errors[] = "الطرد #{$parcel->sequence}: الوزن يجب أن يكون أكبر من 0";
                }
            }
        }

        // COD validation (FR-SH-019)
        if ($shipment->is_cod && $shipment->cod_amount <= 0) {
            $errors[] = 'مبلغ الدفع عند الاستلام يجب أن يكون أكبر من 0';
        }

        return $errors;
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-006: Status Management (State Machine)
    // ═══════════════════════════════════════════════════════════════

    public function updateStatus(string $accountId, string $shipmentId, string $newStatus, User $performer, ?string $reason = null, string $source = 'user'): Shipment
    {
        $shipment = $this->findShipment($accountId, $shipmentId);

        if (!$shipment->canTransitionTo($newStatus)) {
            throw new BusinessException(
                "لا يمكن الانتقال من {$shipment->status} إلى {$newStatus}.",
                'ERR_INVALID_STATUS_TRANSITION', 422
            );
        }

        $this->transitionStatus($shipment, $newStatus, $source, $performer->id, $reason);

        // Side effects based on new status
        if ($newStatus === Shipment::STATUS_DELIVERED) {
            $shipment->update(['actual_delivery_at' => now()]);
            // Update linked order
            if ($shipment->order_id) {
                Order::where('id', $shipment->order_id)->update(['status' => Order::STATUS_DELIVERED]);
            }
        }

        if ($newStatus === Shipment::STATUS_PICKED_UP) {
            $shipment->update(['picked_up_at' => now()]);
            if ($shipment->order_id) {
                Order::where('id', $shipment->order_id)->update(['status' => Order::STATUS_SHIPPED]);
            }
        }

        return $shipment->fresh();
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-007: Cancel / Void
    // ═══════════════════════════════════════════════════════════════

    public function cancelShipment(string $accountId, string $shipmentId, User $performer, ?string $reason = null): Shipment
    {
        $this->assertCanManageShipments($performer);
        $shipment = $this->findShipment($accountId, $shipmentId);

        if (!$shipment->isCancellable()) {
            throw new BusinessException(
                'لا يمكن إلغاء هذه الشحنة في حالتها الحالية.',
                'ERR_SHIPMENT_NOT_CANCELLABLE', 422
            );
        }

        return DB::transaction(function () use ($shipment, $performer, $reason) {
            $oldStatus = $shipment->status;
            $needsRefund = in_array($oldStatus, [Shipment::STATUS_PURCHASED, Shipment::STATUS_READY_FOR_PICKUP]);

            // FR-SH-015: Refund to wallet if already charged
            if ($needsRefund && $shipment->total_charge > 0) {
                $shipment->update(['refund_ledger_entry_id' => 'pending_refund']);
            }

            // Release balance reservation (FR-SH-014)
            if ($shipment->balance_reservation_id) {
                $shipment->update(['balance_reservation_id' => null, 'reserved_amount' => null]);
            }

            $this->transitionStatus($shipment, Shipment::STATUS_CANCELLED, 'user', $performer->id, $reason);
            $shipment->update([
                'cancelled_by'        => $performer->id,
                'cancellation_reason' => $reason ?? 'User requested cancellation',
            ]);

            // Unlink order if linked
            if ($shipment->order_id) {
                Order::where('id', $shipment->order_id)->update([
                    'shipment_id' => null,
                    'status'      => Order::STATUS_READY,
                ]);
            }

            $this->auditService->warning(
                $shipment->account_id, $performer->id,
                'shipment.cancelled', AuditLog::CATEGORY_ACCOUNT,
                'Shipment', $shipment->id,
                ['status' => $oldStatus],
                ['status' => 'cancelled', 'reason' => $reason, 'needs_refund' => $needsRefund]
            );

            return $shipment->fresh();
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-008: Reprint / Download Label
    // ═══════════════════════════════════════════════════════════════

    public function getLabelInfo(string $accountId, string $shipmentId, User $performer): array
    {
        $this->assertCanPrintLabel($performer);
        $shipment = $this->findShipment($accountId, $shipmentId);

        if (!$shipment->hasLabel()) {
            throw new BusinessException('لا يوجد ملصق لهذه الشحنة.', 'ERR_NO_LABEL', 422);
        }

        // Increment print count (FR-SH-012)
        $shipment->increment('label_print_count');

        $this->auditService->info(
            $accountId, $performer->id,
            'shipment.label_printed', AuditLog::CATEGORY_ACCOUNT,
            'Shipment', $shipment->id,
            null,
            ['print_count' => $shipment->label_print_count + 1]
        );

        return [
            'label_url'    => $shipment->label_url,
            'label_format' => $shipment->label_format,
            'print_count'  => $shipment->label_print_count + 1,
            'tracking_number' => $shipment->tracking_number,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-009: Search & Filter
    // ═══════════════════════════════════════════════════════════════

    public function listShipments(string $accountId, array $filters, User $performer): array
    {
        $query = Shipment::where('account_id', $accountId)
            ->with('parcels', 'store:id,name,platform');

        // Filters
        if (!empty($filters['store_id']))   $query->where('store_id', $filters['store_id']);
        if (!empty($filters['status']))     $query->where('status', $filters['status']);
        if (!empty($filters['carrier']))    $query->where('carrier_code', $filters['carrier']);
        if (!empty($filters['source']))     $query->where('source', $filters['source']);
        if (!empty($filters['is_cod']))     $query->where('is_cod', true);
        if (!empty($filters['is_international'])) $query->where('is_international', true);

        if (!empty($filters['from'])) $query->where('created_at', '>=', $filters['from']);
        if (!empty($filters['to']))   $query->where('created_at', '<=', $filters['to']);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('reference_number', 'ilike', "%{$s}%")
                  ->orWhere('tracking_number', 'ilike', "%{$s}%")
                  ->orWhere('recipient_name', 'ilike', "%{$s}%")
                  ->orWhere('recipient_phone', 'ilike', "%{$s}%");
            });
        }

        $limit  = min($filters['limit'] ?? 50, 100);
        $offset = $filters['offset'] ?? 0;

        $total = (clone $query)->count();
        $shipments = $query->orderByDesc('created_at')->limit($limit)->offset($offset)->get();

        // FR-SH-011: Mask financial fields if user lacks permission
        if (!$performer->is_owner && !$performer->hasPermission('shipments:view_financial')) {
            $shipments->each(function ($sh) {
                $sh->makeHidden(['shipping_rate', 'total_charge', 'platform_fee', 'profit_margin', 'insurance_amount']);
            });
        }

        return ['shipments' => $shipments, 'total' => $total, 'limit' => $limit, 'offset' => $offset];
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-010: Bulk Shipment Creation
    // ═══════════════════════════════════════════════════════════════

    public function bulkCreateFromOrders(string $accountId, array $orderIds, array $defaults, User $performer): array
    {
        $this->assertCanManageShipments($performer);

        $results = ['success' => 0, 'failed' => 0, 'errors' => [], 'shipments' => []];

        foreach ($orderIds as $orderId) {
            try {
                $shipment = $this->createFromOrder($accountId, $orderId, $defaults, $performer);
                $results['success']++;
                $results['shipments'][] = ['order_id' => $orderId, 'shipment_id' => $shipment->id, 'reference' => $shipment->reference_number];
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = ['order_id' => $orderId, 'error' => $e->getMessage()];
            }
        }

        $this->auditService->info(
            $accountId, $performer->id,
            'shipment.bulk_created', AuditLog::CATEGORY_ACCOUNT,
            'Account', $accountId,
            null,
            ['total' => count($orderIds), 'success' => $results['success'], 'failed' => $results['failed']]
        );

        return $results;
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-013: KYC Check
    // ═══════════════════════════════════════════════════════════════

    public function checkKycForPurchase(Shipment $shipment): bool
    {
        $account = $shipment->account ?? Account::find($shipment->account_id);

        // KYC required for international or high-value shipments
        if ($shipment->is_international || ($shipment->total_charge && $shipment->total_charge > 2000)) {
            $kycStatus = $account->kyc_status ?? 'pending';
            if ($kycStatus !== 'verified') {
                return false;
            }
        }

        return true;
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-016: Return Shipments
    // ═══════════════════════════════════════════════════════════════

    public function createReturnShipment(string $accountId, string $originalShipmentId, array $overrides, User $performer): Shipment
    {
        $original = $this->findShipment($accountId, $originalShipmentId);

        if (!in_array($original->status, [Shipment::STATUS_DELIVERED, Shipment::STATUS_EXCEPTION])) {
            throw new BusinessException('لا يمكن إنشاء شحنة مرتجع إلا بعد التسليم أو حدوث استثناء.', 'ERR_RETURN_NOT_ALLOWED', 422);
        }

        // Swap sender ↔ recipient
        $data = array_merge([
            'sender_name'         => $original->recipient_name,
            'sender_phone'        => $original->recipient_phone,
            'sender_address_1'    => $original->recipient_address_1,
            'sender_city'         => $original->recipient_city,
            'sender_country'      => $original->recipient_country,
            'recipient_name'      => $original->sender_name,
            'recipient_phone'     => $original->sender_phone,
            'recipient_address_1' => $original->sender_address_1,
            'recipient_city'      => $original->sender_city,
            'recipient_country'   => $original->sender_country,
            'is_return'           => true,
            'store_id'            => $original->store_id,
        ], $overrides);

        $returnShipment = $this->createDirect($accountId, $data, $performer);
        $returnShipment->update([
            'source'   => Shipment::SOURCE_RETURN,
            'metadata' => array_merge($returnShipment->metadata ?? [], ['original_shipment_id' => $originalShipmentId]),
        ]);

        return $returnShipment->fresh(['parcels']);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-004: Address Book
    // ═══════════════════════════════════════════════════════════════

    public function listAddresses(string $accountId, ?string $type = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Address::where('account_id', $accountId);
        if ($type) {
            $query->whereIn('type', [$type, 'both']);
        }
        return $query->orderByDesc('is_default_sender')->orderBy('label')->get();
    }

    public function saveAddress(string $accountId, array $data, User $performer): Address
    {
        // If setting as default sender, unset previous
        if (!empty($data['is_default_sender'])) {
            Address::where('account_id', $accountId)->where('is_default_sender', true)->update(['is_default_sender' => false]);
        }

        return Address::create(array_merge($data, ['account_id' => $accountId]));
    }

    public function deleteAddress(string $accountId, string $addressId): void
    {
        Address::where('account_id', $accountId)->where('id', $addressId)->firstOrFail()->delete();
    }

    // ═══════════════════════════════════════════════════════════════
    // Statistics
    // ═══════════════════════════════════════════════════════════════

    public function getShipmentStats(string $accountId, ?string $storeId = null): array
    {
        $query = Shipment::where('account_id', $accountId);
        if ($storeId) $query->where('store_id', $storeId);

        $byStatus = (clone $query)->selectRaw('status, count(*) as cnt')->groupBy('status')->pluck('cnt', 'status')->toArray();
        $total = array_sum($byStatus);

        return [
            'total'             => $total,
            'draft'             => $byStatus[Shipment::STATUS_DRAFT] ?? 0,
            'purchased'         => $byStatus[Shipment::STATUS_PURCHASED] ?? 0,
            'in_transit'        => $byStatus[Shipment::STATUS_IN_TRANSIT] ?? 0,
            'delivered'         => $byStatus[Shipment::STATUS_DELIVERED] ?? 0,
            'cancelled'         => $byStatus[Shipment::STATUS_CANCELLED] ?? 0,
            'returned'          => $byStatus[Shipment::STATUS_RETURNED] ?? 0,
            'exception'         => $byStatus[Shipment::STATUS_EXCEPTION] ?? 0,
            'by_status'         => $byStatus,
        ];
    }

    public function getShipment(string $accountId, string $shipmentId): Shipment
    {
        return Shipment::where('account_id', $accountId)
            ->where('id', $shipmentId)
            ->with('parcels', 'statusHistory', 'store:id,name,platform', 'order:id,external_order_number,status')
            ->firstOrFail();
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-SH-003: Parcel Management
    // ═══════════════════════════════════════════════════════════════

    public function addParcel(string $accountId, string $shipmentId, array $data, User $performer): Parcel
    {
        $shipment = $this->findShipment($accountId, $shipmentId);
        if (!$shipment->isDraft() && $shipment->status !== Shipment::STATUS_VALIDATED) {
            throw new BusinessException('لا يمكن إضافة طرود بعد تسعير الشحنة.', 'ERR_CANNOT_MODIFY_PARCELS', 422);
        }

        $nextSeq = ($shipment->parcels->max('sequence') ?? 0) + 1;

        $parcel = Parcel::create([
            'shipment_id'    => $shipment->id,
            'sequence'       => $nextSeq,
            'weight'         => $data['weight'],
            'length'         => $data['length'] ?? null,
            'width'          => $data['width'] ?? null,
            'height'         => $data['height'] ?? null,
            'packaging_type' => $data['packaging_type'] ?? 'custom',
            'description'    => $data['description'] ?? null,
            'reference'      => $data['reference'] ?? null,
        ]);

        $parcel->update(['volumetric_weight' => $parcel->calculateVolumetricWeight()]);
        $shipment->recalculateWeights();

        return $parcel;
    }

    public function removeParcel(string $accountId, string $shipmentId, string $parcelId, User $performer): void
    {
        $shipment = $this->findShipment($accountId, $shipmentId);
        if ($shipment->parcels->count() <= 1) {
            throw new BusinessException('لا يمكن حذف آخر طرد.', 'ERR_LAST_PARCEL', 422);
        }

        Parcel::where('id', $parcelId)->where('shipment_id', $shipmentId)->firstOrFail()->delete();
        $shipment->recalculateWeights();
    }

    // ═══════════════════════════════════════════════════════════════
    // Internal Helpers
    // ═══════════════════════════════════════════════════════════════

    private function createParcels(Shipment $shipment, array $parcelsData): void
    {
        foreach ($parcelsData as $i => $pd) {
            $parcel = Parcel::create([
                'shipment_id'    => $shipment->id,
                'sequence'       => $i + 1,
                'weight'         => $pd['weight'] ?? 0.5,
                'length'         => $pd['length'] ?? null,
                'width'          => $pd['width'] ?? null,
                'height'         => $pd['height'] ?? null,
                'packaging_type' => $pd['packaging_type'] ?? 'custom',
                'description'    => $pd['description'] ?? null,
                'reference'      => $pd['reference'] ?? null,
            ]);
            $parcel->update(['volumetric_weight' => $parcel->calculateVolumetricWeight()]);
        }
        $shipment->recalculateWeights();
    }

    private function transitionStatus(Shipment $shipment, string $newStatus, string $source, ?string $userId, ?string $reason): void
    {
        $oldStatus = $shipment->status;
        $shipment->update(['status' => $newStatus, 'status_reason' => $reason]);
        $this->recordStatusChange($shipment, $oldStatus, $newStatus, $source, $userId, $reason);
    }

    private function recordStatusChange(Shipment $shipment, ?string $from, string $to, string $source, ?string $userId, ?string $reason): void
    {
        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'from_status' => $from,
            'to_status'   => $to,
            'source'      => $source,
            'reason'      => $reason,
            'changed_by'  => $userId,
            'created_at'  => now(),
        ]);
    }

    private function findShipment(string $accountId, string $shipmentId): Shipment
    {
        return Shipment::where('account_id', $accountId)
            ->where('id', $shipmentId)
            ->with('parcels')
            ->firstOrFail();
    }

    private function assertCanManageShipments(User $user): void
    {
        if (!$user->is_owner && !$user->hasPermission('shipments:manage')) {
            throw BusinessException::permissionDenied();
        }
    }

    private function assertCanPrintLabel(User $user): void
    {
        if (!$user->is_owner && !$user->hasPermission('shipments:manage') && !$user->hasPermission('shipments:print_label')) {
            throw BusinessException::permissionDenied();
        }
    }
}
