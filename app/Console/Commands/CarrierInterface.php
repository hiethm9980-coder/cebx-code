<?php

namespace App\Services\Contracts;

/**
 * CarrierInterface — C-1: Carrier Integration Skeleton
 *
 * Unified contract for all carrier adapters.
 * Every carrier (DHL, Aramex, SMSA, FedEx, etc.) implements this interface.
 *
 * SKELETON ONLY — no real API calls.
 * Feature flag per carrier: config('features.carrier_{code}')
 */
interface CarrierInterface
{
    /**
     * Get carrier identifier code.
     * Must match CarrierCode enum values (e.g., 'dhl', 'aramex', 'smsa').
     */
    public function code(): string;

    /**
     * Get human-readable carrier name.
     */
    public function name(): string;

    /**
     * Check if this carrier is currently enabled (via feature flag).
     */
    public function isEnabled(): bool;

    /**
     * Create a shipment at the carrier.
     *
     * @param array $payload Shipment data:
     *   - sender_name, sender_phone, sender_address, sender_city, sender_country
     *   - recipient_name, recipient_phone, recipient_address, recipient_city, recipient_country
     *   - weight, dimensions, pieces, description
     *   - service_code, label_format
     * @return array Response:
     *   - success: bool
     *   - shipment_id: string|null
     *   - tracking_number: string|null
     *   - label_url: string|null
     *   - label_content: string|null (base64)
     *   - error: string|null
     */
    public function createShipment(array $payload): array;

    /**
     * Track a shipment.
     *
     * @param string $trackingNumber
     * @return array Response:
     *   - success: bool
     *   - status: string (unified status)
     *   - events: array of ['status', 'description', 'location', 'timestamp']
     *   - error: string|null
     */
    public function track(string $trackingNumber): array;

    /**
     * Cancel a shipment at the carrier.
     *
     * @param string $shipmentId Carrier-side shipment ID
     * @param string $trackingNumber
     * @return array Response:
     *   - success: bool
     *   - cancellation_id: string|null
     *   - error: string|null
     */
    public function cancel(string $shipmentId, string $trackingNumber): array;

    /**
     * Fetch shipping rates for given parameters.
     *
     * @param array $params Origin/destination, weight, dimensions
     * @return array List of rate options:
     *   - service_code, service_name, net_rate, currency, estimated_days
     */
    public function getRates(array $params): array;

    /**
     * Fetch or re-fetch the shipping label.
     *
     * @param string $shipmentId
     * @param string $format 'pdf' or 'zpl'
     * @return array Response:
     *   - success: bool
     *   - content: string (base64)
     *   - format: string
     *   - error: string|null
     */
    public function getLabel(string $shipmentId, string $format = 'pdf'): array;
}
