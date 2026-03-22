<?php

namespace App\Services\Carriers;

use App\Services\Contracts\CarrierInterface;

/**
 * FedexCarrierAdapter — Wraps FedexShipmentProvider + FedexRateProvider
 * to implement CarrierInterface.
 */
class FedexCarrierAdapter implements CarrierInterface
{
    private FedexShipmentProvider $shipmentProvider;
    private FedexRateProvider $rateProvider;
    private FedexTrackingProvider $trackingProvider;

    public function __construct()
    {
        $this->shipmentProvider = new FedexShipmentProvider();
        $this->rateProvider     = new FedexRateProvider();
        $this->trackingProvider = new FedexTrackingProvider();
    }

    public function code(): string
    {
        return 'fedex';
    }

    public function name(): string
    {
        return 'FedEx';
    }

    public function isEnabled(): bool
    {
        return $this->shipmentProvider->isEnabled();
    }

    public function createShipment(array $payload): array
    {
        return $this->shipmentProvider->createShipment($payload);
    }

    public function track(string $trackingNumber): array
    {
        $result = $this->trackingProvider->track($trackingNumber);

        if (!$result['success']) {
            return ['error' => $result['error'] ?? 'Tracking failed', 'events' => []];
        }

        $data = $result['results'][$trackingNumber] ?? reset($result['results']) ?? [];

        return [
            'tracking_number'    => $trackingNumber,
            'status'             => $data['status'] ?? 'unknown',
            'events'             => $data['events'] ?? [],
            'estimated_delivery' => $data['estimated_delivery'] ?? null,
            '_live'              => $data['_live'] ?? false,
        ];
    }

    public function cancel(string $shipmentId, string $trackingNumber): array
    {
        // FedEx cancellation not yet implemented — throws to prevent silent failure.
        // Implement FedexCancellationProvider and wire it here when ready.
        throw new \RuntimeException(
            'FedEx cancellation is not yet implemented. Use the FedEx portal or contact support. '
            . 'Implement FedexCancellationProvider to enable API-based cancellation.'
        );
    }

    public function getRates(array $params): array
    {
        return $this->rateProvider->fetchNetRates($params);
    }

    public function getLabel(string $shipmentId, string $format = 'pdf'): array
    {
        // FedEx label retrieval not yet implemented — throws to prevent silent failure.
        // Implement FedexLabelProvider and wire it here when ready.
        throw new \RuntimeException(
            'FedEx label retrieval is not yet implemented. '
            . 'Implement FedexLabelProvider to enable API-based label fetching.'
        );
    }
}
