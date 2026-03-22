<?php

namespace App\Services\Carriers;

use App\Services\Contracts\CarrierInterface;
use Illuminate\Support\Str;

/**
 * DhlCarrierAdapter — Wraps DhlApiService to implement CarrierInterface.
 *
 * Bridges the gap between DhlApiService method signatures
 * and the standard CarrierInterface contract.
 */
class DhlCarrierAdapter implements CarrierInterface
{
    private DhlApiService $dhl;

    public function __construct()
    {
        $this->dhl = new DhlApiService();
    }

    public function code(): string
    {
        return 'dhl';
    }

    public function name(): string
    {
        return 'DHL Express';
    }

    public function isEnabled(): bool
    {
        return (bool) config('features.carrier_dhl', false)
            && filled((string) config('services.dhl.api_key'))
            && filled((string) config('services.dhl.api_secret'));
    }

    public function createShipment(array $payload): array
    {
        $idempotencyKey = (string) ($payload['idempotency_key'] ?? Str::uuid());

        return $this->dhl->createShipment($payload, $idempotencyKey);
    }

    public function track(string $trackingNumber): array
    {
        return $this->dhl->trackShipment($trackingNumber);
    }

    public function cancel(string $shipmentId, string $trackingNumber): array
    {
        return $this->dhl->cancelShipment($shipmentId, $trackingNumber);
    }

    public function getRates(array $params): array
    {
        return $this->dhl->getRates($params);
    }

    public function getLabel(string $shipmentId, string $format = 'pdf', string $trackingNumber = ''): array
    {
        // DHL fetchLabel requires both carrier_shipment_id and tracking_number as distinct values.
        // When trackingNumber is not supplied (e.g. called directly via CarrierInterface), fall back
        // to shipmentId — note this may cause a DHL API error if the two IDs differ for the shipment.
        // The canonical runtime path (CarrierService::refetchLabel) always passes both distinct values
        // directly to DhlApiService::fetchLabel() without going through this adapter.
        $trackingRef = $trackingNumber !== '' ? $trackingNumber : $shipmentId;
        return $this->dhl->fetchLabel($shipmentId, $trackingRef, $format);
    }
}
