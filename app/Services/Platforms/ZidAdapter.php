<?php

namespace App\Services\Platforms;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ZidAdapter — Zid store integration via OAuth 2.0.
 *
 * API Base: https://api.zid.sa/v1
 * Auth:     Bearer token (access_token from OAuth)
 * Docs:     https://docs.zid.sa/
 *
 * connection_config fields required in Store model:
 *   - access_token  : OAuth access token
 *   - refresh_token : OAuth refresh token
 *   - manager_token : Manager/merchant token (some endpoints require this)
 *   - store_id      : Zid store ID (numeric)
 */
class ZidAdapter implements PlatformAdapterInterface
{
    private const BASE_URL = 'https://api.zid.sa/v1';

    // ─── testConnection ───────────────────────────────────────

    public function testConnection(Store $store): array
    {
        $config = $store->connection_config ?? [];

        if (empty($config['access_token'])) {
            return ['success' => false, 'store_name' => null, 'error' => 'Missing access_token'];
        }

        $response = Http::timeout(10)
            ->withToken($config['access_token'])
            ->withHeaders($this->managerHeader($config))
            ->get(self::BASE_URL . '/managers/store/');

        if (! $response->successful()) {
            return [
                'success'    => false,
                'store_name' => null,
                'error'      => "Zid API error: HTTP {$response->status()}",
            ];
        }

        $store_data = $response->json('store', []);

        return [
            'success'    => true,
            'store_name' => $store_data['name'] ?? $store_data['domain'] ?? null,
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
                ->withHeaders($this->managerHeader($config))
                ->post(self::BASE_URL . '/managers/store/webhooks/', [
                    'url'    => "{$appUrl}/api/v1/webhooks/zid/{$store->id}",
                    'event'  => $event,
                    'status' => 'active',
                ]);

            if ($response->successful()) {
                $registered++;
            } else {
                $errors[] = "Failed to register {$event}: HTTP {$response->status()}";
                Log::warning('Zid webhook registration failed', [
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
            'limit'      => $params['limit'] ?? 50,
            'page'       => $params['page'] ?? 1,
            'status'     => $params['status'] ?? null,
            'start_date' => $params['created_at_min'] ?? null,
        ]);

        $response = Http::timeout(30)
            ->withToken($config['access_token'])
            ->withHeaders($this->managerHeader($config))
            ->get(self::BASE_URL . '/managers/store/orders/', $query);

        if (! $response->successful()) {
            Log::error('Zid fetchOrders failed', [
                'store_id' => $store->id,
                'status'   => $response->status(),
            ]);
            return [];
        }

        return $response->json('orders', []);
    }

    // ─── transformOrder ───────────────────────────────────────

    public function transformOrder(array $rawOrder, Store $store): array
    {
        $customer = $rawOrder['customer'] ?? [];
        $address  = $rawOrder['shipping_address'] ?? $rawOrder['address'] ?? [];
        $amounts  = $rawOrder['payment'] ?? [];

        return [
            'external_order_id'       => (string) ($rawOrder['id'] ?? ''),
            'external_order_number'   => (string) ($rawOrder['code'] ?? $rawOrder['id'] ?? ''),
            'source'                  => 'zid',
            'customer_name'           => $customer['name'] ?? null,
            'customer_email'          => $customer['email'] ?? null,
            'customer_phone'          => $customer['mobile'] ?? $customer['phone'] ?? null,
            'shipping_name'           => $address['name'] ?? $customer['name'] ?? null,
            'shipping_phone'          => $address['phone'] ?? $customer['mobile'] ?? null,
            'shipping_address_line_1' => $address['street'] ?? $address['address_line'] ?? null,
            'shipping_address_line_2' => $address['district'] ?? null,
            'shipping_city'           => $address['city'] ?? null,
            'shipping_state'          => $address['region'] ?? null,
            'shipping_postal_code'    => $address['zip_code'] ?? $address['postal_code'] ?? null,
            'shipping_country'        => $address['country_code'] ?? 'SA',
            'subtotal'                => (float) ($amounts['subtotal'] ?? $rawOrder['subtotal'] ?? 0),
            'shipping_cost'           => (float) ($rawOrder['shipping_amount'] ?? 0),
            'tax_amount'              => (float) ($amounts['tax'] ?? $rawOrder['tax_amount'] ?? 0),
            'discount_amount'         => (float) ($rawOrder['discount_amount'] ?? 0),
            'total_amount'            => (float) ($amounts['total'] ?? $rawOrder['amount'] ?? 0),
            'currency'                => $rawOrder['currency'] ?? 'SAR',
            'items'                   => $this->transformItems($rawOrder['products'] ?? []),
            'external_created_at'     => $rawOrder['created_at'] ?? null,
            'external_updated_at'     => $rawOrder['updated_at'] ?? null,
        ];
    }

    // ─── updateFulfillment ────────────────────────────────────

    public function updateFulfillment(Store $store, string $externalOrderId, string $trackingNumber, string $carrier): array
    {
        $config = $store->connection_config ?? [];

        $response = Http::timeout(15)
            ->withToken($config['access_token'])
            ->withHeaders($this->managerHeader($config))
            ->post(self::BASE_URL . "/managers/store/orders/{$externalOrderId}/shipments/", [
                'tracking_number' => $trackingNumber,
                'shipping_company'=> $carrier,
            ]);

        if (! $response->successful()) {
            Log::error('Zid updateFulfillment failed', [
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
        $secret = $config['webhook_secret'] ?? '';

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
            'order.paid',
        ];
    }

    // ─── Private Helpers ─────────────────────────────────────

    /**
     * Some Zid endpoints require a X-Manager-Token header in addition to Bearer.
     */
    private function managerHeader(array $config): array
    {
        if (! empty($config['manager_token'])) {
            return ['X-Manager-Token' => $config['manager_token']];
        }
        return [];
    }

    private function transformItems(array $products): array
    {
        return array_map(fn ($item) => [
            'external_item_id'  => (string) ($item['id'] ?? ''),
            'sku'               => $item['sku'] ?? null,
            'name'              => $item['name'] ?? 'Unknown',
            'quantity'          => (int) ($item['quantity'] ?? 1),
            'unit_price'        => (float) ($item['price'] ?? $item['unit_price'] ?? 0),
            'total_price'       => (float) ($item['total'] ?? $item['subtotal'] ?? 0),
            'weight'            => isset($item['weight']) ? (float) $item['weight'] : null,
            'hs_code'           => null,
            'country_of_origin' => null,
            'properties'        => isset($item['options']) ? $this->flattenOptions($item['options']) : null,
        ], $products);
    }

    private function flattenOptions(array $options): ?array
    {
        if (empty($options)) return null;

        $result = [];
        foreach ($options as $option) {
            if (! empty($option['name'])) {
                $result[$option['name']] = $option['value'] ?? '';
            }
        }
        return empty($result) ? null : $result;
    }
}
