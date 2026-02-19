<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\StatusMapping;
use App\Models\TrackingEvent;
use App\Models\TrackingSubscription;
use App\Models\User;
use App\Services\Carriers\DhlApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * API Tests — TR Module (FR-TR-001→007)
 *
 * 20 tests covering all tracking API endpoints.
 */
class TrackingApiTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private User $owner;
    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(DhlApiService::class, Mockery::mock(DhlApiService::class));

        $this->account = Account::factory()->create();
        $role = Role::factory()->create(['account_id' => $this->account->id, 'slug' => 'owner']);
        $this->owner = User::factory()->create(['account_id' => $this->account->id, 'role_id' => $role->id]);

        $this->shipment = Shipment::factory()->create([
            'account_id'      => $this->account->id,
            'tracking_number' => 'TRK-API-TEST',
            'status'          => Shipment::STATUS_READY_FOR_PICKUP,
        ]);

        (new \Database\Seeders\DhlStatusMappingSeeder)->run();
    }

    // ═══════════════════════════════════════════════════════════
    // POST /webhooks/dhl/tracking — FR-TR-001/002
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function test_api_dhl_webhook_accepted(): void
    {
        $response = $this->postJson('/api/v1/webhooks/dhl/tracking', [
            'trackingNumber' => 'TRK-API-TEST',
            'events' => [[
                'status' => 'transit',
                'description' => 'In transit',
                'statusCode' => 'DF',
                'timestamp' => now()->toIso8601String(),
            ]],
        ], $this->webhookHeaders());

        $response->assertOk()
            ->assertJsonPath('status', 'processed');
    }

    /** @test */
    public function test_api_dhl_webhook_rejected_bad_signature(): void
    {
        config(['services.dhl.webhook_secret' => 'real-secret']);

        $response = $this->postJson('/api/v1/webhooks/dhl/tracking', [
            'trackingNumber' => 'TRK-API-TEST',
            'events' => [['status' => 'test']],
        ], ['x-dhl-signature' => 'bad']);

        $response->assertStatus(403)
            ->assertJsonPath('status', 'rejected');
    }

    // ═══════════════════════════════════════════════════════════
    // GET /shipments/{id}/tracking/timeline — FR-TR-005
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function test_api_get_tracking_timeline(): void
    {
        TrackingEvent::factory()->count(3)->create([
            'shipment_id' => $this->shipment->id,
            'account_id'  => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/shipments/{$this->shipment->id}/tracking/timeline");

        $response->assertOk()
            ->assertJsonPath('data.total_events', 3)
            ->assertJsonStructure(['data' => ['events' => [['status', 'event_time', 'location']]]]);
    }

    /** @test */
    public function test_api_timeline_empty_for_new_shipment(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/shipments/{$this->shipment->id}/tracking/timeline");

        $response->assertOk()
            ->assertJsonPath('data.total_events', 0);
    }

    // ═══════════════════════════════════════════════════════════
    // GET /tracking/search — FR-TR-005
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function test_api_search_by_status(): void
    {
        $this->shipment->update(['tracking_status' => TrackingEvent::STATUS_IN_TRANSIT]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/tracking/search?status=in_transit');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('data.total'));
    }

    /** @test */
    public function test_api_search_by_tracking_number(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/tracking/search?tracking_number=TRK-API');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('data.total'));
    }

    // ═══════════════════════════════════════════════════════════
    // GET /tracking/dashboard — FR-TR-006
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function test_api_tracking_dashboard(): void
    {
        Shipment::factory()->count(2)->create([
            'account_id' => $this->account->id,
            'tracking_status' => TrackingEvent::STATUS_DELIVERED,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/tracking/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['total_shipments', 'by_status', 'open_exceptions', 'delivery_rate']]);
    }

    /** @test */
    public function test_api_dashboard_with_date_filter(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/tracking/dashboard?date_from=2026-01-01&date_to=2026-12-31');

        $response->assertOk();
    }

    // ═══════════════════════════════════════════════════════════
    // POST /shipments/{id}/tracking/subscribe — FR-TR-004
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function test_api_subscribe_to_tracking(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/shipments/{$this->shipment->id}/tracking/subscribe", [
                'channel'     => 'email',
                'destination' => 'test@example.com',
                'event_types' => ['delivered', 'exception'],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tracking_subscriptions', [
            'shipment_id' => $this->shipment->id,
            'channel'     => 'email',
        ]);
    }

    /** @test */
    public function test_api_subscribe_validates_channel(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/shipments/{$this->shipment->id}/tracking/subscribe", [
                'channel'     => 'pigeon',
                'destination' => 'nope',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_api_unsubscribe(): void
    {
        $sub = TrackingSubscription::factory()->create([
            'shipment_id' => $this->shipment->id,
            'account_id'  => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/tracking/subscriptions/{$sub->id}");

        $response->assertOk();
        $this->assertFalse($sub->fresh()->is_active);
    }

    // ═══════════════════════════════════════════════════════════
    // GET /tracking/status-mappings — FR-TR-004
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function test_api_get_status_mappings(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/tracking/status-mappings?carrier=dhl');

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    // ═══════════════════════════════════════════════════════════
    // Exception Endpoints — FR-TR-007
    // ═══════════════════════════════════════════════════════════

    /** @test */
    public function test_api_get_shipment_exceptions(): void
    {
        ShipmentException::factory()->count(2)->create([
            'shipment_id' => $this->shipment->id,
            'account_id'  => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/shipments/{$this->shipment->id}/exceptions");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_api_acknowledge_exception(): void
    {
        $exception = ShipmentException::factory()->create([
            'shipment_id' => $this->shipment->id,
            'account_id'  => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/exceptions/{$exception->id}/acknowledge");

        $response->assertOk()
            ->assertJsonPath('data.status', 'acknowledged');
    }

    /** @test */
    public function test_api_resolve_exception(): void
    {
        $exception = ShipmentException::factory()->create([
            'shipment_id' => $this->shipment->id,
            'account_id'  => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/exceptions/{$exception->id}/resolve", [
                'notes' => 'Issue resolved by re-delivery',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved');
    }

    /** @test */
    public function test_api_escalate_exception(): void
    {
        $exception = ShipmentException::factory()->create([
            'shipment_id' => $this->shipment->id,
            'account_id'  => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/exceptions/{$exception->id}/escalate");

        $response->assertOk()
            ->assertJsonPath('data.status', 'escalated');
    }

    /** @test */
    public function test_api_resolve_requires_notes(): void
    {
        $exception = ShipmentException::factory()->create([
            'shipment_id' => $this->shipment->id,
            'account_id'  => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/exceptions/{$exception->id}/resolve", []);

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════

    private function webhookHeaders(): array
    {
        return [
            'X-DHL-Signature' => hash_hmac('sha256', '{}', config('services.dhl.webhook_secret', '')),
            'X-DHL-Message-Id' => \Illuminate\Support\Str::uuid()->toString(),
        ];
    }
}
