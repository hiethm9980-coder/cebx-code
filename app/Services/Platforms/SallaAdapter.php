<?php

namespace App\Services\Platforms;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SallaAdapter — Salla store integration via OAuth 2.0.
 *
 * API Base: https://api.salla.dev/admin/v2
 * Auth: Bearer token (access_token from OAuth)
 * Docs: https://docs.salla.dev/
 *
 * connection_config fields required in Store model:
 *   - access_token  : OAuth access token
 *   - refresh_token : OAuth refresh token (for renewal)
 *   - app_secret    : App secret for webhook signature verification
 */
class SallaAdapter implements PlatformAdapterInterface
{
    private const BASE_URL = 'https://api.salla.dev/admin/v2';

    // ─── testConnection ───────────────────────────────────────

    public function testConnection(Store $store): array
    {
        $config = $store->connection_config ?? [];

        if (empty($config['access_token'])) {
            return ['success' => false, 'store_name' => null, 'error' => 'Missing access_token'];
        }

        $response = Http::timeout(10)
            ->withToken($config['access_token'])
            ->get(self::BASE_URL . '/store/info');

        if (! $response->successful()) {
            return [
                'success'    => false,
                'store_name' => null,
                'error'      => "Salla API error: HTTP {$response->status()}",
            ];
        }

        $data = $response->json('data', []);

        return [
            'success'    => true,
            'store_name' => $data['name'] ?? $data['domain'] ?? null,
            'error'      => null,
        ];
    }

    // ─── registerWebhooks ─────────────────────────────────────

    public function registerWebhooks(Store $store): array
    {
        $config  = $store->connection_config ?? [];
        $appUrl  = rtrim(config('app.url'), '/');

        $registered = 0;
        $errors      = [];

        foreach ($this->supportedEvents() as $event) {
            $response = Http::timeout(10)
                ->withToken($config['access_token'])
                ->post(self::BASE_URL . '/webhooks', [
                    'name'   => "CEBX {$event}",
                    'event'  => $event,
                    'url'    => "{$appUrl}/api/v1/webhooks/salla/{$store->id}",
                    'status' => 'active',
                ]);

            if ($response->successful()) {
                $registered++;
            } else {
                $errors[] = "Failed to register {$event}: HTTP {$response->status()}";
                Log::warning('Salla webhook registration failed', [
                    'event'  => $event,
                    'status' => $response->status(),
                ]);
            }
        }

        return [
            'success'             => $registered > 0,
            'webhooks_registered' => $registered,
            'error'               => empty($errors) ? null : implode('; ', $errors),
        ];
    }

    // ─── fetchOrders ──────────────────────────────────────────

    public function fetchOrders(Store $store, array $params = []): array
    {
        $config = $store->connection_config ?? [];

        $query = array_filter([
            'per_page'   => $params['limit'] ?? 50,
            'page'       => $params['page'] ?? 1,
            'status'     => $params['status'] ?? null,
            'created_at' => $params['created_at_min'] ?? null,
        ]);

        $response = Http::timeout(30)
            ->withToken($config['access_token'])
            ->get(self::BASE_URL . '/orders', $query);

        if (! $response->successful()) {
            Log::error('Salla fetchOrders failed', [
                'store_id' => $store->id,
                'status'   => $response->status(),
            ]);
            return [];
        }

        return $response->json('data', []);
    }

    // ─── transformOrder ───────────────────────────────────────

    public function transformOrder(array $rawOrder, Store $store): array
    {
        $customer = $rawOrder['customer'] ?? [];
        $shipping = $rawOrder['shipping'] ?? [];
        $address  = $shipping['address'] ?? [];
        $amounts  = $rawOrder['amounts'] ?? [];

        return [
            'external_order_id'       => (string) ($rawOrder['id'] ?? ''),
            'external_order_number'   => (string) ($rawOrder['reference_id'] ?? $rawOrder['id'] ?? ''),
            'source'                  => 'salla',
            'customer_name'           => $customer['name'] ?? null,
            'customer_email'          => $customer['email'] ?? null,
            'customer_phone'          => $customer['mobile'] ?? null,
            'shipping_name'           => $shipping['name'] ?? $customer['name'] ?? null,
            'shipping_phone'          => $shipping['phone'] ?? $customer['mobile'] ?? null,
            'shipping_address_line_1' => $address['street'] ?? $address['block'] ?? null,
            'shipping_address_line_2' => $address['secondary_number'] ?? null,
            'shipping_city'           => $address['city'] ?? null,
            'shipping_state'          => $address['region'] ?? null,
            'shipping_postal_code'    => $address['postal_code'] ?? null,
            'shipping_country'        => $address['country_code'] ?? 'SA',
            'subtotal'                => (float) ($amounts['subtotal']['amount'] ?? 0),
            'shipping_cost'           => (float) ($amounts['shipping']['amount'] ?? 0),
            'tax_amount'              => (float) ($amounts['tax']['amount'] ?? 0),
            'discount_amount'         => (float) ($amounts['discounts']['amount'] ?? 0),
            'total_amount'            => (float) ($amounts['total']['amount'] ?? 0),
            'currency'                => $rawOrder['currency'] ?? 'SAR',
            'items'                   => $this->transformItems($rawOrder['items'] ?? []),
            'external_created_at'     => $rawOrder['date']['date'] ?? $rawOrder['created_at'] ?? null,
            'external_updated_at'     => $rawOrder['updated_at'] ?? null,
        ];
    }

    // ─── updateFulfillment ────────────────────────────────────

    public function updateFulfillment(Store $store, string $externalOrderId, string $trackingNumber, string $carrier): array
    {
        $config = $store->connection_config ?? [];

        $response = Http::timeout(15)
            ->withToken($config['access_token'])
            ->post(self::BASE_URL . "/orders/{$externalOrderId}/shipments", [
                'company'         => $carrier,
                'tracking_number' => $trackingNumber,
                'type'            => 'local',
            ]);

        if (! $response->successful()) {
            Log::error('Salla updateFulfillment failed', [
                'store_id' => $store->id,
                'order_id' => $externalOrderId,
                'status'   => $response->status(),
            ]);
            return ['success' => false, 'error' => "HTTP {$response->status()}"];
        }

        return ['success' => true, 'error' => null];
    }

    // ─── verifyWebhookSignature ───────────────────────────────

    public function verifyWebhookSignature(string $payload, string $signature, Store $store): bool
    {
        $config = $store->connection_config ?? [];
        $secret = $config['app_secret'] ?? '';

        if (blank($secret)) {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }

    // ─── supportedEvents ─────────────────────────────────────

    public function supportedEvents(): array
    {
        return [
            'order.created',
            'order.updated',
            'order.cancelled',
            'order.payment.updated',
        ];
    }

    // ─── Private Helpers ─────────────────────────────────────

    private function transformItems(array $items): array
    {
        return array_map(fn ($item) => [
            'external_item_id'  => (string) ($item['id'] ?? ''),
            'sku'               => $item['sku'] ?? null,
            'name'              => $item['name'] ?? 'Unknown',
            'quantity'          => (int) ($item['quantity'] ?? 1),
            'unit_price'        => (float) ($item['price']['amount'] ?? $item['price'] ?? 0),
            'total_price'       => (float) ($item['total']['amount'] ?? 0),
            'weight'            => isset($item['weight']) ? (float) $item['weight'] : null,
            'hs_code'           => null,
            'country_of_origin' => null,
            'properties'        => isset($item['options']) ? $this->flattenOptions($item['options']) : null,
        ], $items);
    }

    private function flattenOptions(array $options): ?array
    {
        if (empty($options)) return null;

        $result = [];
        foreach ($options as $option) {
            if (!empty($option['name'])) {
                $result[$option['name']] = $option['value'] ?? '';
            }
        }
        return empty($result) ? null : $result;
    }
}
