<?php

namespace App\Services\Carriers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FedexTrackingProvider — Real FedEx Tracking API v1
 *
 * Implements live tracking lookups via FedEx Track API.
 * Uses same OAuth2 credentials as FedexShipmentProvider.
 *
 * Config (config/services.php → fedex):
 *   FEDEX_CLIENT_ID, FEDEX_CLIENT_SECRET, FEDEX_ACCOUNT_NUMBER
 */
class FedexTrackingProvider
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $accountNumber;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt  = null;

    public function __construct()
    {
        $this->baseUrl       = config('services.fedex.base_url', 'https://apis.fedex.com');
        $this->clientId      = config('services.fedex.client_id', '');
        $this->clientSecret  = config('services.fedex.client_secret', '');
        $this->accountNumber = config('services.fedex.account_number', '');
    }

    public function isEnabled(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret)
            && (bool) config('features.carrier_fedex_tracking', config('features.carrier_fedex', false));
    }

    /**
     * Track one or more FedEx shipments by tracking number.
     *
     * @param string|array $trackingNumbers
     * @return array normalized tracking events
     */
    public function track(string|array $trackingNumbers): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error'   => 'FedEx tracking not enabled — set FEDEX_CLIENT_ID and FEDEX_CLIENT_SECRET',
            ];
        }

        $correlationId = Str::uuid()->toString();
        $numbers = is_array($trackingNumbers) ? $trackingNumbers : [$trackingNumbers];

        try {
            $token = $this->getAccessToken();

            $payload = [
                'includeDetailedScans' => true,
                'trackingInfo'         => array_map(fn($tn) => [
                    'trackingNumberInfo' => ['trackingNumber' => $tn],
                ], $numbers),
            ];

            $response = Http::timeout(30)
                ->withToken($token)
                ->withHeaders([
                    'x-customer-transaction-id' => $correlationId,
                    'x-locale'                  => 'en_US',
                ])
                ->post($this->baseUrl . '/track/v1/trackingnumbers', $payload);

            if (!$response->successful()) {
                $error = $response->json('errors.0.message') ?? 'FedEx tracking API error';
                Log::warning('FedEx tracking failed', [
                    'status'         => $response->status(),
                    'error'          => $error,
                    'correlation_id' => $correlationId,
                ]);
                return ['success' => false, 'error' => $error, 'events' => []];
            }

            $data   = $response->json();
            $output = [];

            foreach ($data['output']['completeTrackResults'] ?? [] as $result) {
                foreach ($result['trackResults'] ?? [] as $trackResult) {
                    $tn     = $result['trackingNumber'] ?? $numbers[0];
                    $events = [];

                    foreach ($trackResult['dateAndTimes'] ?? [] as $dt) {
                        // skip non-event datetime entries
                    }

                    foreach ($trackResult['scanEvents'] ?? [] as $event) {
                        $events[] = [
                            'status'      => $this->mapFedexStatus($event['eventType'] ?? ''),
                            'description' => $event['eventDescription'] ?? '',
                            'location'    => trim(
                                implode(', ', array_filter([
                                    $event['scanLocation']['city'] ?? '',
                                    $event['scanLocation']['stateOrProvinceCode'] ?? '',
                                    $event['scanLocation']['countryCode'] ?? '',
                                ]))
                            ),
                            'timestamp'   => $event['date'] ?? null,
                            'raw_code'    => $event['eventType'] ?? '',
                        ];
                    }

                    $latestStatus = $trackResult['latestStatusDetail'] ?? [];

                    $output[$tn] = [
                        'tracking_number' => $tn,
                        'status'          => $this->mapFedexStatus($latestStatus['code'] ?? ''),
                        'status_label'    => $latestStatus['description'] ?? '',
                        'estimated_delivery' => $trackResult['estimatedDeliveryTimeWindow']['window']['ends'] ?? null,
                        'events'          => $events,
                        '_live'           => true,
                        '_correlation_id' => $correlationId,
                    ];
                }
            }

            return ['success' => true, 'results' => $output];

        } catch (\Throwable $e) {
            Log::error('FedEx tracking exception', [
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return ['success' => false, 'error' => 'FedEx tracking error: ' . $e->getMessage(), 'events' => []];
        }
    }

    // ── OAuth2 Token ────────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiresAt && time() < ($this->tokenExpiresAt - 60)) {
            return $this->accessToken;
        }

        $response = Http::timeout(15)
            ->asForm()
            ->post($this->baseUrl . '/oauth/token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('FedEx OAuth failed: ' . ($response->json('errors.0.message') ?? 'Unknown error'));
        }

        $data = $response->json();
        $this->accessToken   = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    // ── Status Mapping ──────────────────────────────────────────────────────

    private function mapFedexStatus(string $code): string
    {
        return match (strtoupper($code)) {
            'PU', 'PX'           => 'picked_up',
            'OC', 'AR'           => 'in_transit',
            'IT', 'DP'           => 'in_transit',
            'OD'                  => 'out_for_delivery',
            'DL'                  => 'delivered',
            'DE', 'DY', 'SE'     => 'exception',
            'AO', 'CH'           => 'on_hold',
            'RR', 'RL', 'RTO'    => 'returned',
            default               => 'unknown',
        };
    }
}
