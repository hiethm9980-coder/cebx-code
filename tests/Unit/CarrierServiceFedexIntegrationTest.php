<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\BillingWallet;
use App\Models\CarrierDocument;
use App\Models\CarrierShipment;
use App\Models\ContentDeclaration;
use App\Models\Parcel;
use App\Models\RateOption;
use App\Models\RateQuote;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WalletHold;
use App\Services\CarrierService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Carrier createAtCarrier — FedEx Integration Baseline (Service Layer)
 *
 * Purpose:
 *   Regression baseline for the end-to-end path:
 *     CarrierService::createAtCarrier()
 *     → validateForCarrierCreation()           — all 6 guard conditions
 *     → resolveShipmentProvider()              — returns FedexShipmentProvider
 *     → FedexShipmentProvider::createShipment() — HTTP faked
 *     → DB writes: CarrierShipment, CarrierDocument, ShipmentStatusHistory
 *
 * This test calls the service directly (NOT the API endpoint).
 * FedexShipmentCreateApiTest already covers the API path.
 * This covers the service-layer path in isolation.
 *
 * Http::fake() URL patterns are derived from the actual config defaults
 * used by FedexShipmentProvider (see config/services.php):
 *   oauth_url  = https://apis-base.test.cloud.fedex.com/oauth/token
 *   base_url   = https://apis-sandbox.fedex.com
 */
class CarrierServiceFedexIntegrationTest extends TestCase
{
    // These match the FedexShipmentProvider config keys exactly
    private const FEDEX_OAUTH_URL    = 'https://apis-base.test.cloud.fedex.com/oauth/token';
    private const FEDEX_SHIP_URL     = 'https://apis-sandbox.fedex.com/ship/v1/shipments';
    private const FEDEX_TRACKING_NUM = '794699111111';
    private const FIXTURE_RATE       = 345.15;

    // ─── Config injection ─────────────────────────────────────────────────────

    private function configureFedex(): void
    {
        config()->set('features.carrier_fedex', true);
        config()->set('services.fedex.client_id', 'fedex-test-client');
        config()->set('services.fedex.client_secret', 'fedex-test-secret');
        config()->set('services.fedex.account_number', '123456789');
        config()->set('services.fedex.base_url', 'https://apis-sandbox.fedex.com');
        config()->set('services.fedex.oauth_url', self::FEDEX_OAUTH_URL);
        config()->set('services.fedex.locale', 'en_US');
        config()->set('services.fedex.timeout', 30);
    }

    // ─── Http fakes ───────────────────────────────────────────────────────────

    /**
     * Registers Http::fake() for both FedEx endpoints.
     * Pattern follows the same structure as FedexShipmentCreateApiTest::fedexShipFakeResponses().
     * Http::preventStrayRequests() ensures no real HTTP call escapes.
     */
    private function registerFedexFakes(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::FEDEX_OAUTH_URL => fn () => Http::response([
                'access_token' => 'fake-fedex-access-token',
                'token_type'   => 'bearer',
                'expires_in'   => 3600,
            ], 200),
            self::FEDEX_SHIP_URL => fn () => Http::response(
                $this->fedexShipSuccessBody(),
                200
            ),
        ]);
    }

    /**
     * FedEx create-shipment response body.
     * Structure validated against FedexShipmentProvider::normalizeCreateShipmentResponse().
     */
    private function fedexShipSuccessBody(): array
    {
        return [
            'transactionId' => 'fedex-service-unit-tx-' . Str::random(8),
            'output' => [
                'transactionShipments' => [[
                    'masterTrackingNumber' => self::FEDEX_TRACKING_NUM,
                    'serviceType'          => 'INTERNATIONAL_PRIORITY',
                    'serviceName'          => 'FedEx International Priority',
                    'completedShipmentDetail' => [
                        'carrierCode'          => 'FDXE',
                        'masterTrackingNumber' => self::FEDEX_TRACKING_NUM,
                    ],
                    'pieceResponses' => [[
                        'trackingNumber'   => self::FEDEX_TRACKING_NUM,
                        'packageDocuments' => [[
                            'docType'      => 'PDF',
                            'contentType'  => 'LABEL',
                            'copiesToPrint' => 1,
                            // base64-encoded content — storeCarrierDocuments() will decode once
                            'encodedLabel' => base64_encode('FAKE-FEDEX-LABEL-PDF-CONTENT'),
                        ]],
                    ]],
                ]],
            ],
        ];
    }

    // ─── Fixture builder ─────────────────────────────────────────────────────

    /**
     * Builds a Shipment that satisfies ALL validateForCarrierCreation() guards:
     *
     *   Guard 1: shipment->selectedRateOption !== null            → RateOption with carrier_code=fedex
     *   Guard 2: status !== STATUS_REQUIRES_ACTION
     *   Guard 3: status === STATUS_PAYMENT_PENDING
     *   Guard 4: contentDeclaration->status === COMPLETED && !dg
     *   Guard 5: active WalletHold with amount == expected        → amount = retail_rate
     *   Guard 6: parcels()->count() > 0
     *   Guard 7: sender_name && recipient_name non-empty
     *
     * Also satisfies captureActiveReservationForIssuedShipment():
     *   BillingWallet.reserved_balance >= FIXTURE_RATE
     *   BillingWallet.available_balance >= FIXTURE_RATE
     *
     * Uses Schema::hasColumn() guards for columns added by optional migrations.
     *
     * @return array{user: User, shipment: Shipment, wallet: BillingWallet}
     */
    private function buildFixture(): array
    {
        // ── Account & User ────────────────────────────────────────────────────
        // User::factory() does NOT include role_id (users table has no role_id column).
        // CarrierService only uses $user->id for audit logging inside createAtCarrier().
        $account = Account::factory()->create();
        $user    = User::factory()->create([
            'account_id' => (string) $account->id,
            'user_type'  => 'external',
            'status'     => 'active',
        ]);

        // ── Billing wallet ────────────────────────────────────────────────────
        // captureHold() decrements both reserved_balance and available_balance.
        $wallet = BillingWallet::factory()->funded(1000)->create([
            'account_id'       => (string) $account->id,
            'currency'         => 'SAR',
            'reserved_balance' => self::FIXTURE_RATE,
        ]);

        // ── Rate quote + option ───────────────────────────────────────────────
        $rateQuote = RateQuote::factory()->create([
            'account_id'         => (string) $account->id,
            'origin_country'     => 'SA',
            'origin_city'        => 'Riyadh',
            'destination_country'=> 'AE',
            'destination_city'   => 'Dubai',
            'currency'           => 'SAR',
        ]);

        $rateOption = RateOption::query()->create([
            'rate_quote_id'               => (string) $rateQuote->id,
            'carrier_code'                => 'fedex',
            'carrier_name'                => 'FedEx',
            'service_code'                => 'INTERNATIONAL_PRIORITY',
            'service_name'                => 'FedEx International Priority',
            'net_rate'                    => 300.00,
            'fuel_surcharge'              => 45.15,
            'other_surcharges'            => 0.00,
            'total_net_rate'              => self::FIXTURE_RATE,
            'markup_amount'               => 0.00,
            'service_fee'                 => 0.00,
            'retail_rate_before_rounding' => self::FIXTURE_RATE,
            'retail_rate'                 => self::FIXTURE_RATE,
            'profit_margin'               => 0.00,
            'currency'                    => 'SAR',
            'is_available'                => true,
        ]);

        // ── Shipment at STATUS_PAYMENT_PENDING ────────────────────────────────
        // Use Schema::hasColumn() to avoid inserting non-existent columns.
        $shipmentAttributes = [
            'account_id'              => (string) $account->id,
            'user_id'                 => (string) $user->id,
            'created_by'              => (string) $user->id,
            'status'                  => Shipment::STATUS_PAYMENT_PENDING,
            'sender_name'             => 'Test Sender',
            'sender_phone'            => '+966501234567',
            'sender_address_1'        => '123 King Fahd Road',
            'sender_city'             => 'Riyadh',
            'sender_country'          => 'SA',
            'sender_postal_code'      => '12345',
            'recipient_name'          => 'Test Recipient',
            'recipient_phone'         => '+971501234567',
            'recipient_address_1'     => '456 Sheikh Zayed Road',
            'recipient_city'          => 'Dubai',
            'recipient_country'       => 'AE',
            'recipient_postal_code'   => '00000',
            'carrier_code'            => 'fedex',
            'is_international'        => true,
            'has_dangerous_goods'     => false,
            'total_weight'            => 1.5,
            'chargeable_weight'       => 1.5,
        ];

        // Conditionally include columns that may not exist in every migration state
        if (Schema::hasColumn('shipments', 'total_charge')) {
            $shipmentAttributes['total_charge'] = self::FIXTURE_RATE;
        }
        if (Schema::hasColumn('shipments', 'service_code')) {
            $shipmentAttributes['service_code'] = 'INTERNATIONAL_PRIORITY';
        }
        if (Schema::hasColumn('shipments', 'service_name')) {
            $shipmentAttributes['service_name'] = 'FedEx International Priority';
        }

        $shipment = Shipment::factory()->create($shipmentAttributes);

        // Link selected rate option (requires update since factory creates without it)
        $shipment->update([
            'rate_quote_id'           => (string) $rateQuote->id,
            'selected_rate_option_id' => (string) $rateOption->id,
            'status'                  => Shipment::STATUS_PAYMENT_PENDING,
        ]);

        // ── Parcel ────────────────────────────────────────────────────────────
        Parcel::factory()->create([
            'shipment_id' => (string) $shipment->id,
            'weight'      => 1.5,
            'length'      => 20,
            'width'       => 15,
            'height'      => 10,
        ]);

        // ── ContentDeclaration — completed, no dangerous goods ────────────────
        ContentDeclaration::query()->create([
            'account_id'               => (string) $account->id,
            'shipment_id'              => (string) $shipment->id,
            'contains_dangerous_goods' => false,
            'status'                   => ContentDeclaration::STATUS_COMPLETED,
            'waiver_accepted'          => true,
            'declared_by'              => (string) $user->id,
            'declared_at'              => now(),
            'waiver_accepted_at'       => now(),
            'ip_address'               => '127.0.0.1',
            'user_agent'               => 'PHPUnit',
            'locale'                   => 'en',
        ]);

        // ── WalletHold — active, amount == FIXTURE_RATE ───────────────────────
        // resolveExpectedReservationAmount() returns retail_rate (or total_charge if column exists).
        // Both equal FIXTURE_RATE, so the amount comparison always passes.
        $hold = WalletHold::query()->create([
            'wallet_id'      => (string) $wallet->id,
            'account_id'     => (string) $account->id,
            'shipment_id'    => (string) $shipment->id,
            'amount'         => self::FIXTURE_RATE,
            'currency'       => 'SAR',
            'status'         => WalletHold::STATUS_ACTIVE,
            'source'         => 'shipment_preflight',
            'idempotency_key' => 'HOLD-' . Str::upper(Str::random(12)),
            'correlation_id' => 'HOLD-CORR-' . Str::upper(Str::random(8)),
            'actor_id'       => (string) $user->id,
        ]);

        // Link hold to shipment so balanceReservation BelongsTo relation resolves directly.
        // reserved_amount is in Shipment::fillable (added as production fix — was missing, caused MassAssignmentException).
        // resolveExpectedReservationAmount() uses retail_rate from selectedRateOption as fallback.
        $shipment->update([
            'balance_reservation_id' => (string) $hold->id,
        ]);

        // Return fresh with all relations loaded
        $freshShipment = $shipment->fresh([
            'selectedRateOption',
            'rateQuote',
            'balanceReservation',
            'contentDeclaration',
            'parcels',
        ]);

        return ['user' => $user, 'shipment' => $freshShipment, 'wallet' => $wallet];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    /**
     * Baseline: createAtCarrier() returns a CarrierShipment without exception.
     */
    public function test_fedex_create_at_carrier_returns_carrier_shipment(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service = $this->app->make(CarrierService::class);
        $result  = $service->createAtCarrier($shipment, $user);

        $this->assertNotNull($result);
        $this->assertInstanceOf(CarrierShipment::class, $result);
    }

    /**
     * Tracking number, AWB, and carrier_shipment_id are persisted from the fake response.
     */
    public function test_fedex_create_at_carrier_persists_tracking_number(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service         = $this->app->make(CarrierService::class);
        $carrierShipment = $service->createAtCarrier($shipment, $user);

        $this->assertSame(self::FEDEX_TRACKING_NUM, (string) $carrierShipment->tracking_number);
        $this->assertSame(self::FEDEX_TRACKING_NUM, (string) $carrierShipment->awb_number);
        $this->assertSame(self::FEDEX_TRACKING_NUM, (string) $carrierShipment->carrier_shipment_id);
        $this->assertSame('fedex', (string) $carrierShipment->carrier_code);
    }

    /**
     * Label document is extracted from carrier_metadata.package_documents and persisted.
     */
    public function test_fedex_create_at_carrier_stores_label_document(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service         = $this->app->make(CarrierService::class);
        $carrierShipment = $service->createAtCarrier($shipment, $user);

        $this->assertGreaterThan(0, $carrierShipment->documents()->count(),
            'Expected at least one CarrierDocument to be persisted.');

        $label = $carrierShipment->documents()
            ->where('type', CarrierDocument::TYPE_LABEL)
            ->first();

        $this->assertNotNull($label, 'Expected a label-type document.');
        $this->assertTrue((bool) $label->is_available);
        $this->assertNotNull($label->checksum, 'Label must have a checksum (binary was stored).');
    }

    /**
     * With a label document, CarrierShipment advances to STATUS_LABEL_READY.
     */
    public function test_fedex_create_at_carrier_status_is_label_ready(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service         = $this->app->make(CarrierService::class);
        $carrierShipment = $service->createAtCarrier($shipment, $user);

        $this->assertSame(CarrierShipment::STATUS_LABEL_READY, (string) $carrierShipment->status);
    }

    /**
     * Shipment row advances to STATUS_PURCHASED after carrier creation.
     */
    public function test_fedex_create_at_carrier_updates_shipment_status(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service = $this->app->make(CarrierService::class);
        $service->createAtCarrier($shipment, $user);

        $shipment->refresh();
        $this->assertSame(Shipment::STATUS_PURCHASED, (string) $shipment->status);
    }

    /**
     * HTTP requests were dispatched to both FedEx endpoints (OAuth token + ship).
     */
    public function test_fedex_create_at_carrier_sends_requests_to_both_endpoints(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service = $this->app->make(CarrierService::class);
        $service->createAtCarrier($shipment, $user);

        Http::assertSent(static fn ($r) => str_contains($r->url(), 'oauth/token'));
        Http::assertSent(static fn ($r) => str_contains($r->url(), 'ship/v1/shipments'));
    }

    /**
     * Second call with same shipment hits the idempotency guard — returns existing record.
     */
    public function test_fedex_create_at_carrier_is_idempotent(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service = $this->app->make(CarrierService::class);
        $result1 = $service->createAtCarrier($shipment, $user);
        $result2 = $service->createAtCarrier($shipment, $user);

        $this->assertSame($result1->id, $result2->id);
        // FedEx was only called once; second call hit the idempotency guard
        Http::assertSentCount(2); // 1 OAuth + 1 ship
    }

    /**
     * WalletHold transitions to STATUS_CAPTURED after successful creation.
     */
    public function test_fedex_create_at_carrier_captures_wallet_hold(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment, 'wallet' => $wallet] = $this->buildFixture();

        $service = $this->app->make(CarrierService::class);
        $service->createAtCarrier($shipment, $user);

        $hold = WalletHold::where('shipment_id', (string) $shipment->id)->first();
        $this->assertNotNull($hold);
        $this->assertSame(WalletHold::STATUS_CAPTURED, (string) $hold->status);
        $this->assertNotNull($hold->captured_at);

        $wallet->refresh();
        $this->assertEquals(0.00, (float) $wallet->reserved_balance,
            'reserved_balance must drop to 0 after hold capture.');
    }

    /**
     * CarrierShipment is durably persisted in the database.
     */
    public function test_fedex_carrier_shipment_is_persisted_in_database(): void
    {
        $this->configureFedex();
        Storage::fake('local');
        $this->registerFedexFakes();

        ['user' => $user, 'shipment' => $shipment] = $this->buildFixture();

        $service         = $this->app->make(CarrierService::class);
        $carrierShipment = $service->createAtCarrier($shipment, $user);

        $this->assertDatabaseHas('carrier_shipments', [
            'id'              => $carrierShipment->id,
            'tracking_number' => self::FEDEX_TRACKING_NUM,
            'carrier_code'    => 'fedex',
            'status'          => CarrierShipment::STATUS_LABEL_READY,
        ]);
    }
}
