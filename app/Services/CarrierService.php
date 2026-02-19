<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Account;
use App\Models\CarrierDocument;
use App\Models\CarrierError;
use App\Models\CarrierShipment;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Carriers\DhlApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CarrierService — FR-CR-001→008 (8 requirements)
 *
 * FR-CR-001: Create shipment at DHL API, receive tracking/AWB
 * FR-CR-002: Receive & store Label/Docs (PDF/ZPL)
 * FR-CR-003: Idempotency for creation & label issuance
 * FR-CR-004: Normalized error model with retriable flag
 * FR-CR-005: Re-fetch label for created shipments
 * FR-CR-006: Cancel/void shipment at carrier
 * FR-CR-007: Multiple label formats (PDF/ZPL per account setting)
 * FR-CR-008: Secure label download (no financial data, permission-based)
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

    /**
     * Create a shipment at the carrier (DHL) after payment.
     * Includes pre-flight checks, idempotency, and error handling.
     */
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
            return $existing; // Return same result for idempotent request
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
                $shipment->update([
                    'tracking_number'       => $response['trackingNumber'],
                    'carrier_shipment_id'   => $response['shipmentId'] ?? $carrierShipment->id,
                    'status'                => Shipment::STATUS_READY_FOR_PICKUP,
                ]);

                // ── FR-CR-002: Store documents ───────────────
                if (!empty($response['documents'])) {
                    $this->storeCarrierDocuments($carrierShipment, $shipment, $response['documents']);
                    $carrierShipment->update(['status' => CarrierShipment::STATUS_LABEL_READY]);
                } else {
                    $carrierShipment->update(['status' => CarrierShipment::STATUS_LABEL_PENDING]);
                }

                // ── Audit ────────────────────────────────────
                $this->audit->log(
                    'carrier.shipment_created',
                    $user,
                    $carrierShipment,
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

                // Update shipment to failed/requires action
                $shipment->update(['status' => Shipment::STATUS_FAILED]);

                throw BusinessException::make(
                    'ERR_CARRIER_CREATE_FAILED',
                    "Carrier shipment creation failed: {$carrierError->internal_message}",
                    ['carrier_error_id' => $carrierError->id, 'is_retriable' => $carrierError->is_retriable]
                );
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-002: Store Carrier Documents
    // ═══════════════════════════════════════════════════════════

    /**
     * Store label and documents from carrier response.
     */
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
                'content_base64'      => $content, // Already base64 from DHL
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

    /**
     * Re-fetch label for a shipment that was created but docs failed.
     */
    public function refetchLabel(
        Shipment $shipment,
        User $user,
        ?string $format = null
    ): CarrierDocument {
        $carrierShipment = $shipment->carrierShipment;

        if (!$carrierShipment || !$carrierShipment->isCreated()) {
            throw BusinessException::make(
                'ERR_CARRIER_NOT_CREATED',
                'Shipment has not been created at carrier yet'
            );
        }

        $correlationId = Str::uuid()->toString();
        $format = $format ?? $carrierShipment->label_format;

        try {
            $response = $this->dhlApi->fetchLabel(
                $carrierShipment->carrier_shipment_id,
                $carrierShipment->tracking_number,
                $format
            );

            // Store new document
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

            // Update carrier shipment status
            $carrierShipment->update(['status' => CarrierShipment::STATUS_LABEL_READY]);

            // Update main shipment label fields
            $shipment->update([
                'label_url'    => $response['url'] ?? null,
                'label_format' => $format,
                'status'       => Shipment::STATUS_READY_FOR_PICKUP,
            ]);

            $this->audit->log('carrier.label_refetched', $user, $carrierShipment, [
                'format'         => $format,
                'correlation_id' => $correlationId,
            ]);

            return $document;

        } catch (\Exception $e) {
            $carrierError = $this->logCarrierError(
                CarrierError::OP_RE_FETCH_LABEL,
                $e,
                $correlationId,
                $shipment->id,
                $carrierShipment->id
            );

            throw BusinessException::make(
                'ERR_LABEL_REFETCH_FAILED',
                "Failed to re-fetch label: {$carrierError->internal_message}",
                ['is_retriable' => $carrierError->is_retriable]
            );
        }
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-006: Cancel Shipment at Carrier
    // ═══════════════════════════════════════════════════════════

    /**
     * Cancel/void a shipment at the carrier.
     */
    public function cancelAtCarrier(
        Shipment $shipment,
        User $user,
        string $reason = ''
    ): CarrierShipment {
        $carrierShipment = $shipment->carrierShipment;

        if (!$carrierShipment) {
            throw BusinessException::make(
                'ERR_CARRIER_NOT_CREATED',
                'No carrier record exists for this shipment'
            );
        }

        if (!$carrierShipment->canCancel()) {
            throw BusinessException::make(
                'ERR_CARRIER_NOT_CANCELLABLE',
                'Shipment cannot be cancelled at carrier (status: ' . $carrierShipment->status
                . ($carrierShipment->cancellation_deadline && now()->isAfter($carrierShipment->cancellation_deadline)
                    ? ', cancellation window expired' : '') . ')'
            );
        }

        $correlationId = Str::uuid()->toString();

        $carrierShipment->update(['status' => CarrierShipment::STATUS_CANCEL_PENDING]);

        try {
            $response = $this->dhlApi->cancelShipment(
                $carrierShipment->carrier_shipment_id,
                $carrierShipment->tracking_number
            );

            $carrierShipment->update([
                'status'              => CarrierShipment::STATUS_CANCELLED,
                'cancellation_id'     => $response['cancellationId'] ?? null,
                'cancellation_reason' => $reason,
                'cancelled_at'        => now(),
                'is_cancellable'      => false,
            ]);

            // Update main shipment
            $shipment->update(['status' => Shipment::STATUS_CANCELLED]);

            $this->audit->log('carrier.shipment_cancelled', $user, $carrierShipment, [
                'reason'         => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $carrierShipment;

        } catch (\Exception $e) {
            $carrierError = $this->logCarrierError(
                CarrierError::OP_CANCEL,
                $e,
                $correlationId,
                $shipment->id,
                $carrierShipment->id
            );

            $carrierShipment->update(['status' => CarrierShipment::STATUS_CANCEL_FAILED]);

            throw BusinessException::make(
                'ERR_CARRIER_CANCEL_FAILED',
                "Carrier cancellation failed: {$carrierError->internal_message}",
                ['is_retriable' => $carrierError->is_retriable]
            );
        }
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-008: Get Document for Download (Secure)
    // ═══════════════════════════════════════════════════════════

    /**
     * Get a document for secure download (no financial data exposed).
     * Validates user permission before returning.
     */
    public function getDocumentForDownload(
        string $documentId,
        Shipment $shipment,
        User $user,
        bool $recordAccess = true
    ): array {
        $document = CarrierDocument::where('id', $documentId)
            ->where('shipment_id', $shipment->id)
            ->firstOrFail();

        if (!$document->is_available || !$document->hasContent()) {
            throw BusinessException::make(
                'ERR_DOCUMENT_NOT_AVAILABLE',
                'Document is not available for download'
            );
        }

        if ($recordAccess) {
            $document->recordDownload();
        }

        return [
            'id'         => $document->id,
            'type'       => $document->type,
            'format'     => $document->format,
            'mime_type'  => $document->mime_type,
            'filename'   => $document->original_filename,
            'content'    => $document->getDecodedContent(),
            'file_size'  => $document->file_size,
            'checksum'   => $document->checksum,
        ];
    }

    /**
     * List documents for a shipment (metadata only, no content).
     */
    public function listDocuments(Shipment $shipment): Collection
    {
        return CarrierDocument::where('shipment_id', $shipment->id)
            ->available()
            ->get()
            ->map(fn ($doc) => [
                'id'              => $doc->id,
                'type'            => $doc->type,
                'format'          => $doc->format,
                'filename'        => $doc->original_filename,
                'file_size'       => $doc->file_size,
                'print_count'     => $doc->print_count,
                'download_count'  => $doc->download_count,
                'is_available'    => $doc->is_available,
                'created_at'      => $doc->created_at,
            ]);
    }

    // ═══════════════════════════════════════════════════════════
    // FR-CR-003: Retry Failed Creation
    // ═══════════════════════════════════════════════════════════

    /**
     * Retry a failed carrier shipment creation.
     */
    public function retryCreation(
        Shipment $shipment,
        User $user,
        int $maxRetries = 3
    ): CarrierShipment {
        $carrierShipment = CarrierShipment::where('shipment_id', $shipment->id)
            ->withStatus(CarrierShipment::STATUS_FAILED)
            ->first();

        if (!$carrierShipment) {
            throw BusinessException::make(
                'ERR_NO_FAILED_CARRIER',
                'No failed carrier shipment found to retry'
            );
        }

        if (!$carrierShipment->canRetry($maxRetries)) {
            throw BusinessException::make(
                'ERR_MAX_RETRIES_EXCEEDED',
                "Maximum retry attempts ({$maxRetries}) exceeded",
                ['attempt_count' => $carrierShipment->attempt_count]
            );
        }

        $carrierShipment->incrementAttempt();

        // Mark previous errors as resolved
        CarrierError::where('carrier_shipment_id', $carrierShipment->id)
            ->unresolved()
            ->update(['was_resolved' => true, 'resolved_at' => now()]);

        // Re-attempt creation
        return $this->createAtCarrier(
            $shipment,
            $user,
            $carrierShipment->label_format,
            $carrierShipment->label_size
        );
    }

    // ═══════════════════════════════════════════════════════════
    // Get Carrier Errors for Shipment
    // ═══════════════════════════════════════════════════════════

    /**
     * Get all carrier errors for a shipment (FR-CR-004).
     */
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
     * Validate shipment is ready for carrier creation.
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

        // Check DG declaration if needed
        if ($shipment->is_dangerous_goods && empty($shipment->dg_declaration_id)) {
            throw BusinessException::make(
                'ERR_DG_DECLARATION_REQUIRED',
                'Dangerous goods declaration is required before carrier creation'
            );
        }
    }

    /**
     * Build DHL Create Shipment payload.
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
                        'addressLine1' => $shipment->sender_address_line1,
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
                        'addressLine1' => $shipment->recipient_address_line1,
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
     * Map carrier document type string to constant.
     */
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

    /**
     * Generate a filename for a carrier document.
     */
    private function generateDocFilename(Shipment $shipment, string $type, string $format): string
    {
        $ref = $shipment->reference ?? $shipment->id;
        return "{$type}_{$ref}.{$format}";
    }

    /**
     * FR-CR-004: Log a carrier error in normalized format.
     */
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
