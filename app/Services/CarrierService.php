<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CarrierDocument;
use App\Models\CarrierError;
use App\Models\CarrierShipment;
use App\Models\ContentDeclaration;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Models\User;
use App\Services\Carriers\DhlApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CarrierService — FR-CR-001→008 (8 requirements)
 *
 * FIX P0-5: إصلاح أسماء الحقول لتتوافق مع migration
 * FIX P0-2: استخدام BusinessException::make() بعد إضافتها
 * FIX P1-3: تحديث label_url/label_format/label_created_at بعد carrier create
 * FIX P1-1: تسجيل history عند تغيير الحالة
 */
class CarrierService
{
    public function __construct(
        private DhlApiService $dhlApi,
        private AuditService $audit,
        private WalletBillingService $billing,
    ) {}

    // ═══════════════════════════════════════════════════════════
    // FR-CR-001: Create Shipment at Carrier
    // ═══════════════════════════════════════════════════════════

    public function createAtCarrier(
        Shipment $shipment,
        User $user,
        ?string $labelFormat = null,
        ?string $labelSize = null
    ): CarrierShipment {
        $account = $shipment->account;

        // ── Pre-flight validation ────────────────────────────
        $this->validateForCarrierCreation($shipment);

        // ── FR-CR-003: Idempotency check ─────────────────────
        $idempotencyKey = CarrierShipment::generateIdempotencyKey($shipment->id);
        $existing = CarrierShipment::where('idempotency_key', $idempotencyKey)
            ->whereIn('status', [
                CarrierShipment::STATUS_CREATED,
                CarrierShipment::STATUS_LABEL_PENDING,
                CarrierShipment::STATUS_LABEL_READY,
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        // ── FR-CR-007: Determine label format ────────────────
        $format = $labelFormat ?? $account->settings['label_format'] ?? 'pdf';
        $size = $labelSize ?? $account->settings['label_size'] ?? '4x6';

        $correlationId = Str::uuid()->toString();

        return DB::transaction(function () use ($shipment, $account, $user, $format, $size, $idempotencyKey, $correlationId) {
            // Create carrier shipment record
            $carrierShipment = CarrierShipment::create([
                'shipment_id'     => $shipment->id,
                'account_id'      => $account->id,
                'carrier_code'    => CarrierShipment::CARRIER_DHL,
                'carrier_name'    => 'DHL Express',
                'status'          => CarrierShipment::STATUS_CREATING,
                'idempotency_key' => $idempotencyKey,
                'attempt_count'   => 1,
                'last_attempt_at' => now(),
                'label_format'    => $format,
                'label_size'      => $size,
                'correlation_id'  => $correlationId,
            ]);

            try {
                // ── Call DHL API ─────────────────────────────
                $dhlPayload = $this->buildDhlCreatePayload($shipment, $format, $size);
                $carrierShipment->update(['request_payload' => $dhlPayload]);

                $response = $this->dhlApi->createShipment($dhlPayload, $idempotencyKey);

                // ── Process Response ─────────────────────────
                $carrierShipment->update([
                    'carrier_shipment_id'          => $response['shipmentId'] ?? null,
                    'tracking_number'              => $response['trackingNumber'] ?? null,
                    'awb_number'                   => $response['trackingNumber'] ?? null,
                    'dispatch_confirmation_number'  => $response['dispatchConfirmationNumber'] ?? null,
                    'service_code'                 => $response['serviceCode'] ?? $shipment->carrier_service_code,
                    'service_name'                 => $response['serviceName'] ?? null,
                    'product_code'                 => $response['productCode'] ?? null,
                    'status'                       => CarrierShipment::STATUS_CREATED,
                    'response_payload'             => $response,
                    'is_cancellable'               => $response['cancellable'] ?? true,
                    'cancellation_deadline'         => isset($response['cancellationDeadline'])
                        ? \Carbon\Carbon::parse($response['cancellationDeadline'])
                        : now()->addHours(24),
                ]);

                // ── Update shipment with tracking info ───────
                $oldStatus = $shipment->status;
                $shipment->update([
                    'tracking_number'       => $response['trackingNumber'],
                    'carrier_shipment_id'   => $response['shipmentId'] ?? $carrierShipment->id,
                    'status'                => Shipment::STATUS_READY_FOR_PICKUP,
                ]);

                // FIX P1-1: تسجيل history عند تغيير الحالة
                $this->recordStatusHistory($shipment, $oldStatus, Shipment::STATUS_READY_FOR_PICKUP, 'system', $user->id, 'Carrier shipment created');

                // ── FR-CR-002: Store documents ───────────────
                if (!empty($response['documents'])) {
                    $storedDocs = $this->storeCarrierDocuments($carrierShipment, $shipment, $response['documents']);
                    $carrierShipment->update(['status' => CarrierShipment::STATUS_LABEL_READY]);

                    // FIX P1-3: تحديث label_url/label_format/label_created_at بعد تخزين المستندات
                    $this->updateShipmentLabelFromDocuments($shipment, $storedDocs);
                } else {
                    $carrierShipment->update(['status' => CarrierShipment::STATUS_LABEL_PENDING]);
                }

                // ── Audit (FIX P0-3: استخدام التوقيع الصحيح لـ AuditService) ──
                $this->audit->info(
                    $shipment->account_id,
                    $user->id,
                    'carrier.shipment_created',
                    AuditLog::CATEGORY_ACCOUNT,
                    'CarrierShipment',
                    $carrierShipment->id,
                    null,
                    [
                        'tracking_number' => $response['trackingNumber'],
                        'carrier'         => 'dhl',
                        'correlation_id'  => $correlationId,
                    ]
                );

                return $carrierShipment;

            } catch (\Exception $e) {
                // ── FR-CR-004: Log normalized error ──────────
                $carrierError = $this->logCarrierError(
                    CarrierError::OP_CREATE_SHIPMENT,
                    $e,
                    $correlationId,
                    $shipment->id,
                    $carrierShipment->id
                );

                $carrierShipment->update(['status' => CarrierShipment::STATUS_FAILED]);

                $oldStatus = $shipment->status;
                $shipment->update(['status' => Shipment::STATUS_FAILED]);
                $this->recordStatusHistory($shipment, $oldStatus, Shipment::STATUS_FAILED, 'system', null, "Carrier creation failed: {$carrierError->internal_message}");

                throw BusinessException::make(
                    'ERR_CARRIER_CREATE_FAILED',
                    "Carrier shipment creation failed: {$carrierError->internal_message}",
                    ['carrier_error_id' => $carrierError->id, 'is_retriable' => $carrierError->is_retriable]
                );
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    // FIX P1-3: تحديث بيانات الملصق في الشحنة من المستندات المخزنة
    // ═══════════════════════════════════════════════════════════

    private function updateShipmentLabelFromDocuments(Shipment $shipment, Collection $storedDocs): void
    {
        // ابحث عن مستند من نوع label
        $labelDoc = $storedDocs->firstWhere('type', CarrierDocument::TYPE_LABEL);

        if ($labelDoc) {
            $shipment->update([
                'label_url'        => $labelDoc->download_url ?? route('api.v1.shipments.documents.download', [
                    'shipment' => $shipment->id,
                    'document' => $labelDoc->id,
                ]),
                'label_format'     => $labelDoc->format,
                'label_created_at' => now(),
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-002: Store Carrier Documents
    // ═══════════════════════════════════════════════════════════

    private function storeCarrierDocuments(
        CarrierShipment $carrierShipment,
        Shipment $shipment,
        array $documents
    ): Collection {
        $stored = collect();

        foreach ($documents as $doc) {
            $type = $this->mapDocumentType($doc['type'] ?? 'label');
            $format = $doc['format'] ?? $carrierShipment->label_format;
            $content = $doc['content'] ?? null;

            $document = CarrierDocument::create([
                'carrier_shipment_id' => $carrierShipment->id,
                'shipment_id'         => $shipment->id,
                'type'                => $type,
                'format'              => $format,
                'mime_type'           => CarrierDocument::getMimeType($format),
                'original_filename'   => $this->generateDocFilename($shipment, $type, $format),
                'content_base64'      => $content,
                'file_size'           => $content ? strlen(base64_decode($content)) : null,
                'checksum'            => $content ? hash('sha256', base64_decode($content)) : null,
                'download_url'        => $doc['url'] ?? null,
                'download_url_expires_at' => isset($doc['urlExpiry'])
                    ? \Carbon\Carbon::parse($doc['urlExpiry'])
                    : null,
                'is_available'        => !empty($content) || !empty($doc['url']),
            ]);

            $stored->push($document);
        }

        return $stored;
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-005: Re-fetch Label
    // ═══════════════════════════════════════════════════════════

    public function refetchLabel(
        Shipment $shipment,
        User $user,
        ?string $format = null
    ): CarrierDocument {
        $carrierShipment = $shipment->carrierShipment;

        if (!$carrierShipment || !$carrierShipment->isCreated()) {
            throw BusinessException::carrierNotCreated();
        }

        $correlationId = Str::uuid()->toString();
        $format = $format ?? $carrierShipment->label_format;

        try {
            $response = $this->dhlApi->fetchLabel(
                $carrierShipment->carrier_shipment_id,
                $carrierShipment->tracking_number,
                $format
            );

            $document = CarrierDocument::create([
                'carrier_shipment_id' => $carrierShipment->id,
                'shipment_id'         => $shipment->id,
                'type'                => CarrierDocument::TYPE_LABEL,
                'format'              => $format,
                'mime_type'           => CarrierDocument::getMimeType($format),
                'original_filename'   => $this->generateDocFilename($shipment, 'label', $format),
                'content_base64'      => $response['content'] ?? null,
                'file_size'           => isset($response['content'])
                    ? strlen(base64_decode($response['content'])) : null,
                'checksum'            => isset($response['content'])
                    ? hash('sha256', base64_decode($response['content'])) : null,
                'download_url'        => $response['url'] ?? null,
                'is_available'        => true,
            ]);

            $carrierShipment->update(['status' => CarrierShipment::STATUS_LABEL_READY]);

            // FIX P1-3: تحديث الملصق في الشحنة
            $shipment->update([
                'label_url'        => $response['url'] ?? route('api.v1.shipments.documents.download', [
                    'shipment' => $shipment->id,
                    'document' => $document->id,
                ]),
                'label_format'     => $format,
                'label_created_at' => now(),
            ]);

            $this->audit->info(
                $shipment->account_id, $user->id,
                'carrier.label_refetched', AuditLog::CATEGORY_ACCOUNT,
                'CarrierDocument', $document->id,
                null,
                ['format' => $format, 'correlation_id' => $correlationId]
            );

            return $document;

        } catch (\Exception $e) {
            $this->logCarrierError(
                CarrierError::OP_RE_FETCH_LABEL,
                $e,
                $correlationId,
                $shipment->id,
                $carrierShipment->id
            );

            throw BusinessException::labelRefetchFailed();
        }
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-006: Cancel at Carrier
    // ═══════════════════════════════════════════════════════════

    public function cancelAtCarrier(Shipment $shipment, User $user): CarrierShipment
    {
        $carrierShipment = $shipment->carrierShipment;

        if (!$carrierShipment || !$carrierShipment->isCancellable()) {
            throw BusinessException::carrierNotCancellable();
        }

        $correlationId = Str::uuid()->toString();

        try {
            $this->dhlApi->cancelShipment(
                $carrierShipment->carrier_shipment_id,
                $carrierShipment->tracking_number
            );

            $carrierShipment->update(['status' => CarrierShipment::STATUS_CANCELLED]);

            $oldStatus = $shipment->status;
            $shipment->update(['status' => Shipment::STATUS_CANCELLED]);
            $this->recordStatusHistory($shipment, $oldStatus, Shipment::STATUS_CANCELLED, 'user', $user->id, 'Cancelled at carrier');

            $this->audit->info(
                $shipment->account_id, $user->id,
                'carrier.shipment_cancelled', AuditLog::CATEGORY_ACCOUNT,
                'CarrierShipment', $carrierShipment->id,
                null,
                ['correlation_id' => $correlationId]
            );

            return $carrierShipment;

        } catch (\Exception $e) {
            $this->logCarrierError(
                CarrierError::OP_CANCEL,
                $e,
                $correlationId,
                $shipment->id,
                $carrierShipment->id
            );

            throw BusinessException::carrierCancelFailed();
        }
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-003: Retry Failed Creation
    // ═══════════════════════════════════════════════════════════

    public function retryCreation(
        Shipment $shipment,
        User $user,
        int $maxRetries = 3
    ): CarrierShipment {
        $carrierShipment = CarrierShipment::where('shipment_id', $shipment->id)
            ->where('status', CarrierShipment::STATUS_FAILED)
            ->first();

        if (!$carrierShipment) {
            throw BusinessException::make(
                'ERR_NO_FAILED_CARRIER',
                'No failed carrier shipment found to retry'
            );
        }

        if (!$carrierShipment->canRetry($maxRetries)) {
            throw BusinessException::maxRetriesExceeded();
        }

        $carrierShipment->incrementAttempt();

        CarrierError::where('carrier_shipment_id', $carrierShipment->id)
            ->where('was_resolved', false)
            ->update(['was_resolved' => true, 'resolved_at' => now()]);

        return $this->createAtCarrier(
            $shipment,
            $user,
            $carrierShipment->label_format,
            $carrierShipment->label_size
        );
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-008: List & Download Documents
    // ═══════════════════════════════════════════════════════════

    public function listDocuments(Shipment $shipment): array
    {
        return CarrierDocument::where('shipment_id', $shipment->id)
            ->where('is_available', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($doc) => [
                'id'       => $doc->id,
                'type'     => $doc->type,
                'format'   => $doc->format,
                'filename' => $doc->original_filename,
                'size'     => $doc->file_size,
                'available' => $doc->hasContent() || $doc->hasValidUrl(),
                'created_at' => $doc->created_at,
            ])
            ->toArray();
    }

    public function getErrors(Shipment $shipment): Collection
    {
        return CarrierError::where('shipment_id', $shipment->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($error) => [
                'id'                    => $error->id,
                'operation'             => $error->operation,
                'internal_code'         => $error->internal_code,
                'internal_message'      => $error->internal_message,
                'carrier_error_code'    => $error->carrier_error_code,
                'carrier_error_message' => $error->carrier_error_message,
                'http_status'           => $error->http_status,
                'is_retriable'          => $error->is_retriable,
                'retry_attempt'         => $error->retry_attempt,
                'was_resolved'          => $error->was_resolved,
                'created_at'            => $error->created_at,
            ]);
    }

    // ═══════════════════════════════════════════════════════════
    // Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * FIX P0-5: إصلاح التحقق ليستخدم has_dangerous_goods بدلاً من is_dangerous_goods
     * وإزالة الاعتماد على dg_declaration_id (يتم عبر DgComplianceService)
     */
    private function validateForCarrierCreation(Shipment $shipment): void
    {
        // Must be in purchased/payment_pending status
        if (!in_array($shipment->status, [Shipment::STATUS_PURCHASED, Shipment::STATUS_PAYMENT_PENDING])) {
            throw BusinessException::make(
                'ERR_INVALID_STATE_FOR_CARRIER',
                "Shipment must be in purchased state (current: {$shipment->status})"
            );
        }

        // Must have sender and recipient addresses
        if (empty($shipment->sender_name) || empty($shipment->recipient_name)) {
            throw BusinessException::make(
                'ERR_MISSING_ADDRESS',
                'Shipment must have complete sender and recipient addresses'
            );
        }

        // Must have at least one parcel
        if ($shipment->parcels()->count() === 0) {
            throw BusinessException::make(
                'ERR_NO_PARCELS',
                'Shipment must have at least one parcel'
            );
        }

        // FIX P0-5: استخدام has_dangerous_goods بدلاً من is_dangerous_goods
        // والتحقق من DG declaration عبر جدول content_declarations بدلاً من dg_declaration_id
        if ($shipment->has_dangerous_goods) {
            $declaration = ContentDeclaration::where('shipment_id', $shipment->id)
                ->where('account_id', $shipment->account_id)
                ->where('status', ContentDeclaration::STATUS_COMPLETED)
                ->first();

            if (!$declaration) {
                throw BusinessException::make(
                    'ERR_DG_DECLARATION_REQUIRED',
                    'Dangerous goods declaration is required and must be completed before carrier creation'
                );
            }
        }
    }

    /**
     * FIX P0-5: إصلاح أسماء الحقول في DHL Payload
     * - sender_address_line1 → sender_address_1
     * - recipient_address_line1 → recipient_address_1
     */
    private function buildDhlCreatePayload(Shipment $shipment, string $labelFormat, string $labelSize): array
    {
        $parcels = $shipment->parcels->map(fn ($p) => [
            'weight'     => $p->weight,
            'dimensions' => [
                'length' => $p->length,
                'width'  => $p->width,
                'height' => $p->height,
            ],
            'description' => $p->description ?? 'Package',
        ])->toArray();

        return [
            'plannedShippingDateAndTime' => now()->format('Y-m-d\TH:i:s \G\M\TP'),
            'pickup' => [
                'isRequested' => false,
            ],
            'productCode'   => $shipment->carrier_service_code ?? 'P',
            'accounts'      => [
                ['typeCode' => 'shipper', 'number' => config('services.dhl.account_number')],
            ],
            'outputImageProperties' => [
                'imageOptions' => [
                    ['typeCode' => 'label', 'templateName' => strtoupper($labelSize)],
                ],
                'encodingFormat' => strtoupper($labelFormat),
            ],
            'customerDetails' => [
                'shipperDetails' => [
                    'postalAddress' => [
                        'postalCode'   => $shipment->sender_postal_code,
                        'cityName'     => $shipment->sender_city,
                        'countryCode'  => $shipment->sender_country,
                        // FIX P0-5: sender_address_1 بدلاً من sender_address_line1
                        'addressLine1' => $shipment->sender_address_1,
                    ],
                    'contactInformation' => [
                        'phone'       => $shipment->sender_phone,
                        'companyName' => $shipment->sender_company ?? $shipment->sender_name,
                        'fullName'    => $shipment->sender_name,
                        'email'       => $shipment->sender_email ?? '',
                    ],
                ],
                'receiverDetails' => [
                    'postalAddress' => [
                        'postalCode'   => $shipment->recipient_postal_code,
                        'cityName'     => $shipment->recipient_city,
                        'countryCode'  => $shipment->recipient_country,
                        // FIX P0-5: recipient_address_1 بدلاً من recipient_address_line1
                        'addressLine1' => $shipment->recipient_address_1,
                    ],
                    'contactInformation' => [
                        'phone'       => $shipment->recipient_phone,
                        'companyName' => $shipment->recipient_company ?? $shipment->recipient_name,
                        'fullName'    => $shipment->recipient_name,
                        'email'       => $shipment->recipient_email ?? '',
                    ],
                ],
            ],
            'content' => [
                'packages'    => $parcels,
                'isCustomsDeclarable' => $shipment->is_international,
                'declaredValue'       => $shipment->declared_value ?? 0,
                'declaredValueCurrency' => $shipment->currency ?? 'SAR',
                'description' => $shipment->content_description ?? 'Shipment',
                'unitOfMeasurement' => 'metric',
            ],
        ];
    }

    /**
     * FIX P1-1: تسجيل history عند كل تغيير في حالة الشحنة
     */
    private function recordStatusHistory(
        Shipment $shipment,
        string $fromStatus,
        string $toStatus,
        string $source = 'system',
        ?string $changedBy = null,
        ?string $reason = null
    ): void {
        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'source'      => $source,
            'reason'      => $reason,
            'changed_by'  => $changedBy,
            'created_at'  => now(),
        ]);
    }

    private function mapDocumentType(string $type): string
    {
        return match (strtolower($type)) {
            'label', 'waybill_doc'       => CarrierDocument::TYPE_LABEL,
            'invoice', 'commercial'      => CarrierDocument::TYPE_COMMERCIAL_INVOICE,
            'customs', 'cn23', 'cn22'    => CarrierDocument::TYPE_CUSTOMS_DECLARATION,
            'waybill', 'awb'             => CarrierDocument::TYPE_WAYBILL,
            'receipt'                     => CarrierDocument::TYPE_RECEIPT,
            default                       => CarrierDocument::TYPE_OTHER,
        };
    }

    private function generateDocFilename(Shipment $shipment, string $type, string $format): string
    {
        $ref = $shipment->reference_number ?? $shipment->id;
        return "{$type}_{$ref}.{$format}";
    }

    private function logCarrierError(
        string $operation,
        \Exception $e,
        string $correlationId,
        ?string $shipmentId = null,
        ?string $carrierShipmentId = null
    ): CarrierError {
        $httpStatus = method_exists($e, 'getCode') ? (int) $e->getCode() : 0;
        $responseBody = null;

        if (method_exists($e, 'getResponse')) {
            try {
                $responseBody = json_decode($e->getResponse()->getBody()->getContents(), true);
            } catch (\Exception $_) {}
        }

        return CarrierError::fromDhlResponse(
            $operation,
            $httpStatus ?: 500,
            $responseBody ?? ['message' => $e->getMessage()],
            $correlationId,
            $shipmentId,
            $carrierShipmentId,
            ['exception_class' => get_class($e)]
        );
    }
}
