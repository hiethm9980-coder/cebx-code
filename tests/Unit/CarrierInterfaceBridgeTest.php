<?php

namespace Tests\Unit;

use App\Services\Carriers\CarrierInterfaceBridge;
use App\Services\Carriers\Contracts\CarrierShipmentProvider;
use App\Services\Contracts\CarrierInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;

/**
 * CarrierInterfaceBridgeTest
 *
 * Pure unit test — no DB, no Laravel container, no HTTP.
 * Uses an anonymous fake adapter implementing CarrierInterface.
 *
 * Covers:
 *   1. carrierCode() maps from adapter->code()
 *   2. isEnabled() passthrough
 *   3. createShipment() passthrough — payload and response
 *   4. Exception passthrough — bridge does not swallow exceptions
 */
class CarrierInterfaceBridgeTest extends BaseTestCase
{
    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Build a fake CarrierInterface with configurable code/enabled/response.
     */
    private function fakeAdapter(
        string $code = 'dhl',
        string $name = 'DHL Express',
        bool   $enabled = true,
        array  $shipmentResponse = [],
    ): CarrierInterface {
        return new class($code, $name, $enabled, $shipmentResponse) implements CarrierInterface {
            public function __construct(
                private string $code,
                private string $name,
                private bool   $enabled,
                private array  $shipmentResponse,
            ) {}

            public function code(): string    { return $this->code; }
            public function name(): string    { return $this->name; }
            public function isEnabled(): bool { return $this->enabled; }

            public function createShipment(array $payload): array
            {
                return $this->shipmentResponse;
            }

            public function track(string $trackingNumber): array          { return []; }
            public function cancel(string $shipmentId, string $trackingNumber): array { return []; }
            public function getRates(array $params): array                { return []; }
            public function getLabel(string $shipmentId, string $format = 'pdf'): array { return []; }
        };
    }

    /**
     * Build a fake adapter whose createShipment() always throws.
     */
    private function throwingAdapter(string $code = 'dhl'): CarrierInterface
    {
        return new class($code) implements CarrierInterface {
            public function __construct(private string $code) {}

            public function code(): string    { return $this->code; }
            public function name(): string    { return 'Throwing'; }
            public function isEnabled(): bool { return true; }

            public function createShipment(array $payload): array
            {
                throw new RuntimeException('Carrier API unreachable');
            }

            public function track(string $trackingNumber): array          { return []; }
            public function cancel(string $shipmentId, string $trackingNumber): array { return []; }
            public function getRates(array $params): array                { return []; }
            public function getLabel(string $shipmentId, string $format = 'pdf'): array { return []; }
        };
    }

    // ─── Contract assertion ──────────────────────────────────────────────────

    /** Bridge must itself satisfy CarrierShipmentProvider. */
    public function test_bridge_implements_carrier_shipment_provider(): void
    {
        $bridge = new CarrierInterfaceBridge($this->fakeAdapter());

        $this->assertInstanceOf(CarrierShipmentProvider::class, $bridge);
    }

    // ─── Test 1: carrierCode() maps from adapter->code() ────────────────────

    public function test_carrier_code_returns_adapter_code(): void
    {
        $bridge = new CarrierInterfaceBridge($this->fakeAdapter(code: 'dhl'));

        $this->assertSame('dhl', $bridge->carrierCode());
    }

    public function test_carrier_code_reflects_adapter_code_for_different_carriers(): void
    {
        foreach (['fedex', 'aramex', 'ups', 'smsa'] as $code) {
            $bridge = new CarrierInterfaceBridge($this->fakeAdapter(code: $code));
            $this->assertSame($code, $bridge->carrierCode(), "carrierCode() failed for carrier '{$code}'");
        }
    }

    // ─── Test 2: isEnabled() passthrough ────────────────────────────────────

    public function test_is_enabled_returns_true_when_adapter_enabled(): void
    {
        $bridge = new CarrierInterfaceBridge($this->fakeAdapter(enabled: true));

        $this->assertTrue($bridge->isEnabled());
    }

    public function test_is_enabled_returns_false_when_adapter_disabled(): void
    {
        $bridge = new CarrierInterfaceBridge($this->fakeAdapter(enabled: false));

        $this->assertFalse($bridge->isEnabled());
    }

    // ─── Test 3: createShipment() passthrough ───────────────────────────────

    public function test_create_shipment_passes_context_to_adapter_and_returns_response(): void
    {
        $expectedResponse = [
            'carrier_code'       => 'dhl',
            'carrier_name'       => 'DHL Express',
            'carrier_shipment_id' => 'DHL-98765',
            'tracking_number'    => 'TRK-00001',
            'awb_number'         => 'TRK-00001',
            'service_code'       => 'EXPRESS',
            'service_name'       => 'DHL Express Worldwide',
            'initial_carrier_status' => 'created',
            'request_payload'    => ['accountNumber' => ['value' => 'ACCT']],
            'response_payload'   => ['output' => ['transactionShipments' => [[]]]],
            'carrier_metadata'   => [
                'provider'            => 'dhl',
                'shipment_documents'  => [],
                'package_documents'   => [
                    ['contentType' => 'LABEL', 'encodedLabel' => base64_encode('fake-label-pdf')],
                ],
            ],
        ];

        $adapter = $this->fakeAdapter(shipmentResponse: $expectedResponse);
        $bridge  = new CarrierInterfaceBridge($adapter);

        $context = [
            'shipment_id'    => 'ship-uuid-001',
            'carrier_code'   => 'dhl',
            'service_code'   => 'EXPRESS',
            'label_format'   => 'pdf',
            'label_size'     => '4x6',
            'correlation_id' => 'corr-uuid-001',
            'sender_name'    => 'Test Sender',
            'sender_country' => 'SA',
            'recipient_name' => 'Test Recipient',
            'recipient_country' => 'AE',
            'total_weight'   => 2.5,
            'parcels'        => [['weight' => 2.5, 'length' => null, 'width' => null, 'height' => null]],
        ];

        $result = $bridge->createShipment($context);

        $this->assertSame($expectedResponse, $result);
    }

    public function test_create_shipment_passes_context_unchanged_to_adapter(): void
    {
        $receivedPayload = null;

        $adapter = new class($receivedPayload) implements CarrierInterface {
            public function __construct(private mixed &$captured) {}

            public function code(): string    { return 'dhl'; }
            public function name(): string    { return 'DHL'; }
            public function isEnabled(): bool { return true; }

            public function createShipment(array $payload): array
            {
                $this->captured = $payload;
                return ['carrier_code' => 'dhl', 'tracking_number' => 'TRK-001'];
            }

            public function track(string $t): array          { return []; }
            public function cancel(string $s, string $t): array { return []; }
            public function getRates(array $p): array         { return []; }
            public function getLabel(string $s, string $f = 'pdf'): array { return []; }
        };

        $bridge = new CarrierInterfaceBridge($adapter);
        $context = ['shipment_id' => 'ship-001', 'carrier_code' => 'dhl', 'correlation_id' => 'c-001'];

        $bridge->createShipment($context);

        $this->assertSame($context, $receivedPayload, 'Bridge must pass context to adapter unchanged');
    }

    // ─── Test 4: Exception passthrough ──────────────────────────────────────

    public function test_create_shipment_does_not_swallow_adapter_exception(): void
    {
        $bridge = new CarrierInterfaceBridge($this->throwingAdapter());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Carrier API unreachable');

        $bridge->createShipment(['shipment_id' => 'ship-002']);
    }

    public function test_exception_from_adapter_propagates_without_wrapping(): void
    {
        $bridge = new CarrierInterfaceBridge($this->throwingAdapter(code: 'fedex'));

        try {
            $bridge->createShipment([]);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            // Bridge must NOT wrap in another exception type
            $this->assertSame(RuntimeException::class, get_class($e));
            $this->assertSame('Carrier API unreachable', $e->getMessage());
        }
    }
}
