<?php

namespace App\Services\Contracts;

/**
 * PaymentGatewayInterface — C-2: Payment Gateway Contract
 *
 * Unified contract for all payment gateway adapters.
 * Every gateway (Moyasar, Stripe, STC Pay, etc.) implements this interface.
 *
 * Feature flag per gateway: config('features.payment_{slug}')
 */
interface PaymentGatewayInterface
{
    /** Gateway identifier slug (e.g., 'moyasar', 'stripe', 'stcpay'). */
    public function slug(): string;

    /** Human-readable gateway name. */
    public function name(): string;

    /** Check if this gateway is enabled via feature flag. */
    public function isEnabled(): bool;

    /** Check if running in sandbox/test mode. */
    public function isSandbox(): bool;

    /**
     * Charge a payment.
     *
     * @param array $params amount, currency, payment_method, source_token, description, metadata, idempotency_key
     * @return array success, transaction_id, status (captured|authorized|pending|failed), amount, currency, error, gateway_response
     */
    public function charge(array $params): array;

    /**
     * Refund a previous charge.
     *
     * @param array $params transaction_id, amount, reason, idempotency_key
     * @return array success, refund_id, amount, error
     */
    public function refund(array $params): array;

    /**
     * Verify a payment status (e.g., after 3DS redirect).
     *
     * @param string $transactionId Gateway transaction ID
     * @return array success, status, amount, error
     */
    public function verify(string $transactionId): array;

    /** @return array e.g. ['card', 'apple_pay', 'mada'] */
    public function supportedMethods(): array;

    /** @return array e.g. ['SAR', 'AED', 'USD'] */
    public function supportedCurrencies(): array;
}
