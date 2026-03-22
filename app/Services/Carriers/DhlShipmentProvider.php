<?php

namespace App\Services\Carriers;

use App\Services\Carriers\Contracts\CarrierShipmentProvider;
use Illuminate\Support\Str;

/**
 * DhlShipmentProvider — Adapts DhlApiService to the CarrierShipmentProvider contract.
 *
 * Bridges the signature difference between DhlApiService::createShipment(payload, key)
 * and the CarrierShipmentProvider::createShipment(context) interface required by
 * CarrierService::createAtCarrier(). Also normalises the DHL camelCase response
 * to the standard snake_case response envelope used throughout CarrierService.
 */
class DhlShipmentProvider implements CarrierShipmentProvider
{
    public function __construct(private DhlApiService $dhl) {}

    public function carrierCode(): string
    {
        return 'dhl';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function createShipment(array $context): array
    {
        $idempotencyKey = (string) ($context['idempotency_key'] ?? Str::uuid());

        $raw = $this->dhl->createShipment($context, $idempotencyKey);

        return $this->normalizeResponse($raw);
    }

    /**
     * Normalise the DHL API response (camelCase keys) to the standard
     * CarrierService envelope (snake_case keys).
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalizeResponse(array $raw): array
    {
        $trackingNumber = $raw['trackingNumber'] ?? $raw['tracking_number'] ?? null;
        $shipmentId     = $raw['shipmentId']     ?? $raw['carrier_shipment_id'] ?? null;

        return [
            'carrier_shipment_id' => (string) ($shipmentId ?? ''),
            'tracking_number'     => $trackingNumber !== null ? (string) $trackingNumber : null,
            'awb_number'          => $trackingNumber !== null ? (string) $trackingNumber : null,
            'service_code'        => $raw['serviceCode'] ?? $raw['service_code'] ?? null,
            'service_name'        => $raw['serviceName'] ?? $raw['service_name'] ?? null,
            'carrier_code'        => 'dhl',
            'carrier_name'        => 'DHL Express',
            'is_cancellable'      => (bool) ($raw['cancellable'] ?? false),
            'documents'           => $raw['documents'] ?? [],
            'carrier_metadata'    => array_filter([
                'dispatch_confirmation_number' => $raw['dispatchConfirmationNumber'] ?? null,
                'product_code'                 => $raw['productCode'] ?? null,
                'initial_carrier_status'       => 'created',
            ], static fn ($v) => $v !== null),
            'initial_carrier_status' => 'created',
        ];
    }
}
