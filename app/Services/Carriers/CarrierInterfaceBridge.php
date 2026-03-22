<?php

namespace App\Services\Carriers;

use App\Services\Carriers\Contracts\CarrierShipmentProvider;
use App\Services\Contracts\CarrierInterface;

/**
 * CarrierInterfaceBridge
 *
 * Adapts a CarrierInterface instance (produced by CarrierAdapterFactory)
 * to the CarrierShipmentProvider contract expected by CarrierService.
 *
 * Resolves the sole naming gap:
 *   CarrierShipmentProvider::carrierCode() ← CarrierInterface::code()
 *
 * This bridge is NOT wired into CarrierService yet.
 * It exists only to establish and verify the contract translation.
 * Runtime integration requires a separate, explicit change to
 * CarrierService::resolveShipmentProvider() in a future sprint.
 */
class CarrierInterfaceBridge implements CarrierShipmentProvider
{
    public function __construct(
        private readonly CarrierInterface $adapter,
    ) {}

    public function carrierCode(): string
    {
        return $this->adapter->code();
    }

    public function isEnabled(): bool
    {
        return $this->adapter->isEnabled();
    }

    public function createShipment(array $context): array
    {
        return $this->adapter->createShipment($context);
    }
}
